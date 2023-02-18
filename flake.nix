{
  description = "bdelespierre/laravel-blade-linter";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-22.11";
    flake-utils = {
      url = "github:numtide/flake-utils";
    };
    gitignore = {
      url = "github:hercules-ci/gitignore.nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    pre-commit-hooks = {
      url = "github:cachix/pre-commit-hooks.nix";
      inputs.nixpkgs.follows = "nixpkgs";
      inputs.nixpkgs-stable.follows = "nixpkgs";
      inputs.flake-utils.follows = "flake-utils";
      inputs.gitignore.follows = "gitignore";
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

      src = pkgs.lib.cleanSourceWith {
        name = "laravel-blade-linter-source";
        src = ./.;
        filter = gitignore.lib.gitignoreFilterWith {
          basePath = ./.;
          extraRules = ''
            .dockerignore
            .codeclimate.json
            .editorconfig
            .envrc
            .gitattributes
            .github
            .gitignore
            *.nix
            *.md
          '';
        };
      };

      makePackage = php': let
        php = php'.withExtensions ({
          enabled,
          all,
        }:
          enabled ++ [all.ast]);
      in
        ((pkgs.callPackage ./composer-project.nix {
            inherit php;
            phpPackages = php.packages;
          })
          src)
        .overrideAttrs (old: {
          passthru.php = php;
          preInstall = ''
            export sourceRoot=laravel-blade-linter
          '';
          postInstall = ''
            ln -s $out/libexec/laravel-blade-linter/bin/blade-linter $out/bin/blade-linter
          '';
          meta = {
            mainProgram = "blade-linter";
          };
        });

      makeCheck = package:
        pkgs.runCommand "blah" {buildInputs = [package.php];} ''
          cp -r --no-preserve=all ${package}/libexec/laravel-blade-linter/* .
          ${pkgs.lib.getExe package.php} ./vendor/bin/phpunit | tee $out
        '';

      php81Package = makePackage pkgs.php81;
      php82Package = makePackage pkgs.php82;

      pre-commit-check = pre-commit-hooks.lib.${system}.run {
        # we want to use the uncleaned src here...
        src = ./.;
        #inherit src;
        hooks = {
          actionlint.enable = true;
          alejandra.enable = true;
          alejandra.excludes = ["\/vendor\/"];
          markdownlint.enable = true;
          phpcs.enable = true;
          shellcheck.enable = true;
        };
      };
    in rec {
      packages = rec {
        php81 = php81Package;
        php82 = php82Package;
        default = php81;
      };

      checks = {
        inherit pre-commit-check;
        php81 = makeCheck packages.php81;
        php82 = makeCheck packages.php82;
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
