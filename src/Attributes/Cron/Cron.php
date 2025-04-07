<?php

namespace NimblePHP\Framework\Attributes\Cron;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Cron
{

    /**
     * Time
     * @var string
     */
    public string $time;

    /**
     * Priority
     * @var int
     */
    public int $priority;

    /**
     * Parameters
     * @var array 
     */
    public array $parameters;

    /**
     * @param string $value
     * @param int $priority
     * @param array $parameters
     */
    public function __construct(string $time, int $priority = null, array $parameters = [])
    {
        $this->time = $time;
        $this->priority = $priority ?? \NimblePHP\Framework\Cron::PRIORITY_NORMAL;
        $this->parameters = $parameters;
    }

}