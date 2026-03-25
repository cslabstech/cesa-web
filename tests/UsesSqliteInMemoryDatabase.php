<?php

namespace Tests;

trait UsesSqliteInMemoryDatabase
{
    protected function useSqliteInMemoryDatabase(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('DB_FOREIGN_KEYS=true');

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_ENV['DB_FOREIGN_KEYS'] = 'true';

        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_FOREIGN_KEYS'] = 'true';
    }
}
