#!/usr/bin/env bats

@test "lint valid blade template" {
    run ./bin/blade-linter lint -v tests/views/valid.blade.php
    [ "$status" -eq 0 ]
    [[ "${output}" =~ "No syntax errors" ]]
}

@test "lint invalid blade template" {
    run ./bin/blade-linter lint -v tests/views/invalid.blade.php
    [ "$status" -ne 0 ]
    [[ ! "${output}" =~ "No syntax errors" ]]
}
