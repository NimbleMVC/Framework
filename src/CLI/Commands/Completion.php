<?php

namespace NimblePHP\Framework\CLI\Commands;

use NimblePHP\Framework\CLI\AbstractCommand;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;

#[ConsoleCommand(
    command: 'completion',
    description: 'Wygeneruj skrypt uzupełniania bash',
    help: 'Generate a bash completion script for the Nimble CLI.',
    usage: 'php vendor/bin/nimble completion',
    examples: [
        ['command' => 'php vendor/bin/nimble completion', 'description' => 'Print the bash completion script to stdout.'],
    ]
)]
class Completion extends AbstractCommand
{

    public function handle(): int
    {
        $this->output()->write($this->scriptTemplate());

        return 0;
    }

    public function generate(): void
    {
        $this->output()->write($this->scriptTemplate());
    }

    /**
     * @return string
     */
    private function scriptTemplate(): string
    {
        $nimblePath = realpath($_SERVER['SCRIPT_FILENAME'] ?? __FILE__);

        return <<<BASH
#!/usr/bin/env bash

_nimble_completion() {
    local cur prev words cword
    _init_completion || return

    if [[ \$cword -eq 1 ]]; then
        local commands=\$(php "$nimblePath" --complete)
        COMPREPLY=( \$(compgen -W "\$commands" -- "\$cur") )
        return 0
    fi
    
    return 0
}

_php_nimble_completion() {
    local cur prev words cword
    _init_completion || return

    if [[ \$cword -eq 1 && \$prev == "php" ]]; then
        COMPREPLY=( \$(compgen -f -- "\$cur") )
        return 0
    fi

    if [[ \$cword -eq 2 && (\$prev == *"nimble" || \$prev == *"bin/nimble") ]]; then
        local commands=\$(php "\$prev" --complete)
        COMPREPLY=( \$(compgen -W "\$commands" -- "\$cur") )
        return 0
    fi
    
    return 0
}

if type complete &>/dev/null; then
    complete -F _nimble_completion nimble
    complete -F _nimble_completion vendor/bin/nimble
    complete -F _nimble_completion ./vendor/bin/nimble
    complete -F _nimble_completion bin/nimble
    complete -F _nimble_completion ./bin/nimble
    
    complete -F _php_nimble_completion php
fi

if [[ "\${BASH_SOURCE[0]}" == "\${0}" ]]; then
    exit 0
fi
BASH;
    }

}
