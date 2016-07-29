#!/usr/bin/env php
<?php

exec("ln -s ../../.githooks/pre-commit ".__DIR__."/../.git/hooks/pre-commit");
exec("chmod +x ".__DIR__ . "/../.git/hooks/pre-commit");
