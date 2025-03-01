<?php

namespace NimblePHP\framework\Attributes\Http;

use NimblePHP\framework\Exception\NotFoundException;
use NimblePHP\framework\Interfaces\ControllerInterface;
use NimblePHP\framework\Request;

/**
 * Set action definition
 */
#[\Attribute]
class Action
{

    /**
     * Action type
     * @var string
     */
    public string $type;

    /**
     * @param string $type Action type
     *
     * "disabled" - disable method for user
     *
     * "ajax" - only ajax action
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * Handler
     * @param ControllerInterface $controller
     * @param string $method
     * @param array $params
     * @return void
     * @throws NotFoundException
     */
    public function handle(ControllerInterface $controller, string $method, array $params = []): void
    {
        switch ($this->type) {
            case 'disabled':
                throw new NotFoundException('Method ' . $method . ' is disabled');
            case 'ajax':
                if (!(new Request())->isAjax()) {
                    throw new NotFoundException('Method ' . $method . ' is not allowed for AJAX requests');
                }
                break;
        }
    }

}