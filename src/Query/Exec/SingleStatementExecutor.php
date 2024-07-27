<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Utility\LockSqlHelper;

/**
 * Executor that executes the SQL statement for simple DQL SELECT/UPDATE/DELETE statements.
 *
 * @link        www.doctrine-project.org
 */
class SingleStatementExecutor extends AbstractSqlExecutor
{
    use LockSqlHelper;

    private bool $isSelectStatement = false;

    public function __construct(AST\Node $AST, SqlWalker $sqlWalker)
    {
        if ($AST instanceof AST\SelectStatement) {
            $this->sqlStatements = $sqlWalker->walkSelectStatement($AST);
            $this->isSelectStatement = true;
        } elseif ($AST instanceof AST\UpdateStatement) {
            $this->sqlStatements = $sqlWalker->walkUpdateStatement($AST);
        } elseif ($AST instanceof AST\DeleteStatement) {
            $this->sqlStatements = $sqlWalker->walkDeleteStatement($AST);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Connection $conn, array $params, array $types): Result|int
    {
        if ($conn instanceof PrimaryReadReplicaConnection) {
            $conn->ensureConnectedToPrimary();
        }

        if ($this->isSelectStatement) {
            return $conn->executeQuery($this->sqlStatements, $params, $types, $this->queryCacheProfile);
        } else {
            return $conn->executeStatement($this->sqlStatements, $params, $types);
        }
    }
}
