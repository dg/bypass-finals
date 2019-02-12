<?php
declare(strict_types=1);

final class Foo // to not be skipped
{
}


function getMagic()
{
	return [__FILE__, __DIR__, eval('return __DIR__;')];
}
