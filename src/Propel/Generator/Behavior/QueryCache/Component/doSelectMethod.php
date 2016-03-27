<?php
/**
 * This file is part of the Propel2 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\QueryCache\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;

class doSelectMethod extends BuildComponent
{
    public function process()
    {
        $body = "
// check that the columns of the main class are already added (if this is the primary ModelCriteria)
if (!\$this->hasSelectClause() && !\$this->getPrimaryCriteria()) {
    \$this->addSelfSelectFields();
}
\$this->configureSelectFields();

\$dbMap = \$this->getConfiguration()->getDatabase(\$this->getDbName());

\$con = \$this->getConfiguration()->getConnectionManager(\$this->getDbName())->getReadConnection();
\$adapter = \$this->getConfiguration()->getAdapter(\$this->getDbName());

\$key = \$this->getQueryKey();
if (\$key && \$this->cacheContains(\$key)) {
    \$params = \$this->getParams();
    \$sql = \$this->cacheFetch(\$key);
} else {
    \$params = array();
    \$sql = \$this->createSelectSql(\$params);
    if (\$key) {
        \$this->cacheStore(\$key, \$sql);
    }
}

try {
    \$stmt = \$con->prepare(\$sql);
    \$p = [];
    foreach (\$params as \$param) {
        \$p[] = \$param['value'];
    }
    \$this->getConfiguration()->debug(\"doSelect() sql: \$sql [\" . implode(',', \$p). \"]\");
    \$adapter->bindValues(\$stmt, \$params, \$dbMap);
    \$stmt->execute();
} catch (\\Exception \$e) {
    if (isset(\$stmt)) {
        \$stmt = null; // close
    }
    \$this->getConfiguration()->log(\$e->getMessage(), Configuration::LOG_ERR);
    throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', \$sql), null, \$e);
}

return \$con->getDataFetcher(\$stmt);
";
        $this->addMethod('doSelect')->setBody($body);
    }
}
