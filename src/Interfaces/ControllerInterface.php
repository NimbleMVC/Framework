<?php

namespace Nimblephp\framework\Interfaces;

use Nimblephp\framework\Abstracts\AbstractModel;
use Nimblephp\framework\Exception\NimbleException;
use Nimblephp\framework\Exception\NotFoundException;

interface ControllerInterface
{

    /**
     * Load model
     * @param string $name
     * @return AbstractModel
     * @throws NimbleException
     * @throws NotFoundException
     */
    public function loadModel(string $name): AbstractModel;

}