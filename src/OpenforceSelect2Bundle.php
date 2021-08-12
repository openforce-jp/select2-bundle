<?php
namespace Openforce\Select2Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OpenforceSelect2Bundle extends Bundle
{
    public function getPath()
    {
        return \dirname(__DIR__);
    }
}