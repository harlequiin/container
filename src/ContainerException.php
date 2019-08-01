<?php
declare(strict_types=1);

namespace harlequiin\Container;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \Exception implements ContainerExceptionInterface 
{
}
