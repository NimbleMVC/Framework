<?php

namespace NimblePHP\Framework\Abstracts\Middlewares;

use NimblePHP\Framework\Interfaces\ViewMiddlewareInterface;

abstract class AbstractViewMiddleware implements ViewMiddlewareInterface
{

    /**
     * Before render
     * @param string &$template
     * @param array &$data
     * @return void
     */
    public function beforeRender(string &$template, array &$data): void {}

    /**
     * After render
     * @param string $template
     * @param array $data
     * @param string &$output
     * @return void
     */
    public function afterRender(string $template, array $data, string &$output): void {}

    /**
     * Before assign
     * @param string $key
     * @param mixed &$value
     * @return void
     */
    public function beforeAssign(string $key, &$value): void {}

    /**
     * After assign
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function afterAssign(string $key, $value): void {}

    /**
     * Before include
     * @param string &$file
     * @param array &$data
     * @return void
     */
    public function beforeInclude(string &$file, array &$data): void {}

    /**
     * After include
     * @param string $file
     * @param array $data
     * @param string &$output
     * @return void
     */
    public function afterInclude(string $file, array $data, string &$output): void {}

}