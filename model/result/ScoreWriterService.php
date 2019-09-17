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

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLtiConsumer\model\result\ResultException;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoLtiConsumer\model\result\MessagesService;

/**
 * Class LtiXmlFormatterService
 * Class to manage XML result data with score and to store it in DeliveryExecution
 * @package oat\taoLtiConsumer\model\classes
 */
class ScoreWriterService extends ConfigurableService
{
    public function store($result)
    {
        $this->isDeliveryExecutionExists($result);

        /** @var ResultServerService $resultServerService */
        $resultServerService = $this->getServiceManager()->get(ResultServerService::SERVICE_ID);
        $resultStorageService = $resultServerService->getResultStorage($result['sourcedId']);
        $resultStorageService->storeTestVariable($result['sourcedId'], '', $this->resultService->getScoreVariable($result), '');
    }

    /**
     * @param array $result
     * @return bool
     * @throws ResultException
     */
    public function isDeliveryExecutionExists($result)
    {
        try {
            /** @var ServiceProxy $resultService */
            $resultService = $this->getServiceManager()->get(ServiceProxy::SERVICE_ID);
            $resultService->getDeliveryExecution($result['sourcedId']);
        } catch (\Exception $e) {
            throw new ResultException($e->getMessage(), MessagesService::STATUS_DELIVERY_EXECUTION_NOT_FOUND, null,
                MessagesService::buildMessageData(MessagesService::STATUS_DELIVERY_EXECUTION_NOT_FOUND, $result)
            );
        }

        return true;
    }

}
