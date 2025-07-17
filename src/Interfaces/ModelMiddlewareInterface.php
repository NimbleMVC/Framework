<?php

namespace NimblePHP\Framework\Interfaces;

interface ModelMiddlewareInterface
{

    /**
     * Before save
     * @param array &$data
     * @return void
     */
    public function beforeSave(array &$data): void;

    /**
     * After save
     * @param array $data
     * @param bool $result
     * @return void
     */
    public function afterSave(array $data, bool $result): void;

    /**
     * Before find
     * @param array &$conditions
     * @return void
     */
    public function beforeFind(array &$conditions): void;

    /**
     * After find
     * @param array $conditions
     * @param array $result
     * @return void
     */
    public function afterFind(array $conditions, array $result): void;

    /**
     * Before delete
     * @param array &$conditions
     * @return void
     */
    public function beforeDelete(array &$conditions): void;

    /**
     * After delete
     * @param array $conditions
     * @param bool $result
     * @return void
     */
    public function afterDelete(array $conditions, bool $result): void;

    /**
     * Before update
     * @param array &$data
     * @param array &$conditions
     * @return void
     */
    public function beforeUpdate(array &$data, array &$conditions): void;

    /**
     * After update
     * @param array $data
     * @param array $conditions
     * @param bool $result
     * @return void
     */
    public function afterUpdate(array $data, array $conditions, bool $result): void;

    /**
     * Before insert
     * @param array &$data
     * @return void
     */
    public function beforeInsert(array &$data): void;

    /**
     * After insert
     * @param array $data
     * @param bool $result
     * @return void
     */
    public function afterInsert(array $data, bool $result): void;

}