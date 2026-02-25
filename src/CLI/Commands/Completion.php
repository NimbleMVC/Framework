<?php

namespace NimblePHP\Framework\CLI\Commands;

use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;

class Completion
{

    #[ConsoleCommand(command: 'completion', description: 'Wygeneruj skrypt uzupełniania bash')]
    public function generate(): void
    {
        $nimblePath = realpath($_SERVER['SCRIPT_FILENAME'] ?? __FILE__);

        echo <<<BASH
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