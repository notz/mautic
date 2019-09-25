<?php

declare(strict_types=1);

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\IntegrationsBundle\Migration;

use Doctrine\ORM\EntityManager;
use MauticPlugin\IntegrationsBundle\Exception\PathNotFoundException;

class Engine
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $tablePrefix;

    /**
     * @var string
     */
    private $migrationsPath;

    /**
     * @param EntityManager $entityManager
     * @param string        $tablePrefix
     * @param string        $pluginPath
     */
    public function __construct(EntityManager $entityManager, string $tablePrefix, string $pluginPath)
    {
        $this->entityManager  = $entityManager;
        $this->tablePrefix    = $tablePrefix;
        $this->migrationsPath = __DIR__.'/Migrations/';
    }

    /**
     * Run available migrations.
     */
    public function up(): void
    {
        $migrationClasses = $this->getMigrationClasses();

        if (!$migrationClasses) {
            return;
        }

        $this->entityManager->beginTransaction();

        foreach ($migrationClasses as $migrationClass) {
            /** @var AbstractMigration $migration */
            $migration = new $migrationClass($this->entityManager, $this->tablePrefix);

            if ($migration->shouldExecute()) {
                $migration->execute();
            }
        }

        $this->entityManager->commit();
    }

    /**
     * Get migration classes to proceed.
     *
     * @return string[]
     */
    private function getMigrationClasses(): array
    {
        $migrationFileNames = $this->getMigrationFileNames();
        $migrationClasses   = [];

        foreach ($migrationFileNames as $fileName) {
            require_once $this->migrationsPath.$fileName;
            $className          = preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileName);
            $className          = "MauticPlugin\CustomObjectsBundle\Migrations\\${className}";
            $migrationClasses[] = $className;
        }

        return $migrationClasses;
    }

    /**
     * Get migration file names.
     *
     * @return string[]
     */
    private function getMigrationFileNames(): array
    {
        $fileNames = scandir($this->migrationsPath);

        if (false === $fileNames) {
            throw new PathNotFoundException(
                sprintf("'%s' directory not found", $this->migrationsPath)
            );
        }

        return array_diff($fileNames, ['.', '..']);
    }
}
