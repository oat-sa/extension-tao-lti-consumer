<?php
/**
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA
 */

namespace oat\taoLtiConsumer\model\result;

use common_exception_Error;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\oatbox\service\ServiceManagerAwareTrait;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoResultServer\models\Exceptions\DuplicateVariableException;
use taoResultServer_models_classes_OutcomeVariable as ResultServerOutcomeVariable;

/**
 * Class LtiXmlFormatterService
 * Class to manage XML result data with score and to store it in DeliveryExecution
 * @package oat\taoLtiConsumer\model\classes
 */
class ScoreWriterService extends ConfigurableService
{
    use ServiceManagerAwareTrait;

    /**
     * Store the score result into a delivery execution
     * @param $result
     * @return string
     * @throws InvalidServiceManagerException
     * @throws ResultException
     * @throws common_exception_Error
     * @throws DuplicateVariableException
     */
    public function store($result)
    {
        if (!(isset($result['score']) && $this->isScoreValid($result['score']))) {
            throw new InvalidScoreException(
                MessagesService::$statuses[MessagesService::STATUS_INVALID_SCORE], MessagesService::STATUS_INVALID_SCORE, null,
                MessagesService::buildMessageData(MessagesService::STATUS_INVALID_SCORE, $result)
            );
        }

        $deliveryExecution = $this->getDeliveryExecution($result);


        /** @var ResultServerService $resultServerService */
        $resultServerService = $this->getServiceManager()->get(ResultServerService::SERVICE_ID);
        $resultStorageService = $resultServerService->getResultStorage($result['sourcedId']);
        $resultStorageService->storeTestVariable($result['sourcedId'], '', $this->getScoreVariable($deliveryExecution->getIdentifier(), $result['score']), '');

        return $deliveryExecution->getIdentifier();
    }

    /**
     * Look for a DeliveryExecution and return it or throw an Exception
     * @param array $result
     * @throws ResultException
     * @return DeliveryExecution
     */
    private function getDeliveryExecution($result)
    {
        try {
            /** @var ServiceProxy $resultService */
            $resultService = $this->getServiceManager()->get(ServiceProxy::SERVICE_ID);
            $deliveryExecution = $resultService->getDeliveryExecution($result['sourcedId']);
            $deliveryExecution->getDelivery();
        } catch (\Exception $e) {
            throw new ResultException($e->getMessage(), MessagesService::STATUS_DELIVERY_EXECUTION_NOT_FOUND, null,
                MessagesService::buildMessageData(MessagesService::STATUS_DELIVERY_EXECUTION_NOT_FOUND, $result)
            );
        }

        return $deliveryExecution;
    }

    /**
     * @param $identifier
     * @param string $score
     * @return ResultServerOutcomeVariable
     * @throws \common_exception_InvalidArgumentType
     */
    private function getScoreVariable($identifier, $score)
    {
        $scoreVariable = new ResultServerOutcomeVariable();
        $scoreVariable->setIdentifier('SCORE');
        $scoreVariable->setCardinality(ResultServerOutcomeVariable::CARDINALITY_SINGLE);
        $scoreVariable->setBaseType('float');
        $scoreVariable->setEpoch(microtime());
        $scoreVariable->setValue($score);

        return $scoreVariable;
    }

    /**
     * @param mixed $score
     * @return bool
     */
    private function isScoreValid($score)
    {
        return (is_numeric($score) && $score >= 0 && $score <= 1);
    }
}
