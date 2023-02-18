{
  description = "bdelespierre/laravel-blade-linter";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-22.11";
    flake-utils = {
      url = "github:numtide/flake-utils";
    };
    pre-commit-hooks = {
      url = "github:cachix/pre-commit-hooks.nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    gitignore = {
      url = "github:hercules-ci/gitignore.nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
  };

  outputs = {
    self,
    nixpkgs,
    flake-utils,
    pre-commit-hooks,
    gitignore,
  }:
    flake-utils.lib.eachDefaultSystem (system: let
      pkgs = nixpkgs.legacyPackages.${system};

      php = pkgs.php80.buildEnv {
        extraConfig = ''
          memory_limit=2G
        '';
        extensions = {
          enabled,
          all,
        }:
          enabled ++ [all.ast];
      };

      phpWithPcov = php.withExtensions ({
        enabled,
        all,
      }:
        enabled ++ [all.pcov]);

      src = gitignore.lib.gitignoreSource ./.;

      pre-commit-check = pre-commit-hooks.lib.${system}.run {
        inherit src;
        hooks = {
          actionlint.enable = true;
          alejandra.enable = true;
          alejandra.excludes = ["\/vendor\/"];
          phpcs.enable = true;
          shellcheck.enable = true;
        };
      };
    in rec {
      checks = {
        inherit pre-commit-check;
      };

      devShells.default = pkgs.mkShell {
        buildInputs = with pkgs; [
          actionlint
          bats
          mdl
          nixpkgs-fmt
          php
          php.packages.composer
          php.packages.phpcs
          pre-commit
        ];
        shellHook = ''
          ${pre-commit-check.shellHook}
          export PATH="$PWD/vendor/bin:$PATH"
          export PHP_WITH_PCOV="${phpWithPcov}/bin/php"
          export PHPUNIT_WITH_PCOV="$PHP_WITH_PCOV -d memory_limit=512M -d pcov.directory=$PWD -dpcov.exclude="~vendor~" ./vendor/bin/phpunit"
        '';
      };

      formatter = pkgs.alejandra;
    });
}
