#!/usr/bin/env bats

bats_require_minimum_version 1.5.0

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

@test "lint valid blade template with codeclimate" {
    run --separate-stderr ./bin/blade-linter lint -v --codeclimate=stderr tests/views/valid.blade.php
    [ "$status" -eq 0 ]
    [[ "${output}" =~ "No syntax errors" ]]
    [[ "${stderr}" = "[]" ]]
    echo "${stderr}" | jq
}

@test "lint invalid blade template with codeclimate" {
    local stderr=
    run --separate-stderr ./bin/blade-linter lint -v --codeclimate=stderr tests/views/invalid.blade.php
    [ "$status" -ne 0 ]
    [[ ! "${output}" =~ "No syntax errors" ]]
    [[ "${stderr}" =~ "check_name" ]]
    echo "${stderr}" | jq
}
