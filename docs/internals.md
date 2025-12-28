# BypassFinals internals

A `file://` stream wrapper that strips `final`/`readonly` from PHP source on load
(so final classes can be mocked). Small, but three things are expensive to
rediscover: the wrapper **chaining**, the token-removal rules, and the re-entrancy
dance.

## It chains onto an existing wrapper, it does not just replace

`enable()` inspects the currently registered `file` wrapper and records it as
`MutatingWrapper::$underlyingWrapperClass` (the existing one, or `NativeWrapper` if
native), then registers `MutatingWrapper` in its place. So BypassFinals **composes**
with a pre-existing custom `file` wrapper (e.g. another tool's) rather than clobbering
it — `MutatingWrapper` delegates every real file op to that underlying wrapper via
`__call`. Two consequences:

- The underlying is held as a plain `object` on purpose, so a **foreign wrapper that
  only duck-types the streamWrapper protocol** is accepted at runtime.
- `enable()` is **idempotent** — if the current wrapper is already a `MutatingWrapper`
  it returns early, so re-enabling won't stack wrappers on themselves.

`stream_open` modifies only for a **`rb` open of a `.php` file whose path passes
`isPathAllowed`**: it reads the whole source, runs `modifyCode`, and — **only if the
code changed** — closes the underlying handle and serves the modified source from a
fresh `NativeWrapper` over a `tmpfile()`; unchanged source just `seek(0)`s and streams
from the underlying. Everything non-`.php`/non-`rb` passes straight through.

## Token removal has non-obvious rules

`removeTokens` tokenizes (`token_get_all(..., TOKEN_PARSE)`; a `ParseError` leaves the
code untouched) and drops keywords with care:

- **`final`** is dropped when the next significant token (skipping visibility/`static`
  modifiers) is `class`/`function`/`readonly` — but **`final ... const` is kept**
  (final constants are a real feature, not a mockability barrier).
- **`readonly`** is dropped before a `class`, or when a visibility modifier sits on
  either side; but a **visibility-less promoted `readonly T $x` (PHP 8.4+) becomes
  `public`** rather than vanishing, so it stays a promoted constructor property.
  `final`/`abstract` are skipped so `readonly final class` is handled like
  `final readonly class`.

These rules are the reason the removal is token-based, not a regex.

## Re-entrancy: cache I/O must restore the wrapper once

`removeTokensCached` runs **inside an active `MutatingWrapper::stream_open` callback**.
Doing file I/O there would recurse back into the wrapper, and the comment is explicit:
**multiple `stream_wrapper_restore` cycles inside that callback corrupt PHP's internal
stream-wrapper state.** So all cache reads/writes are wrapped in a single
`stream_wrapper_restore(...)` … `finally { unregister + re-register MutatingWrapper }`
block (with `flock` for concurrent-safe cache files keyed by
`sha1(code + tokens)`). Preserve that single-restore structure when editing.

`isPathAllowed` is allow-then-deny by `fnmatch` (allow defaults to `['*']`, deny is
checked only within a matched allow). `enable()` also snapshots the call stack and the
classes already loaded before it ran, purely for `debugInfo()` diagnostics of "why
wasn't this class un-finalized" (it was loaded too early).
