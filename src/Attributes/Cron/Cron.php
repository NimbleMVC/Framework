<?php

namespace NimblePHP\Framework\Attributes\Cron;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
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
     * Run after date
     * @var string|null
     */
    public ?string $runAfterDate;

    /**
     * Expiration date
     * @var string|null
     */
    public ?string $expirationDate;

    /**
     * @param string $time
     * @param int|null $priority
     * @param array $parameters
     * @param string|null $runAfterDate
     * @param string|null $expirationDate
     */
    public function __construct(string $time, ?int $priority = null, array $parameters = [], ?string $runAfterDate = null, ?string $expirationDate = null)
    {
        $this->time = $time;
        $this->priority = $priority ?? \NimblePHP\Framework\Cron::PRIORITY_NORMAL;
        $this->parameters = $parameters;
        $this->runAfterDate = $runAfterDate;
        $this->expirationDate = $expirationDate;
    }

}
