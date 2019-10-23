<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 */
namespace oat\taoLtiConsumer\model\result\validator;

use oat\taoLtiConsumer\model\result\InvalidScoreException;
use oat\taoLtiConsumer\model\result\MessageBuilder;
use oat\taoLtiConsumer\model\result\ResultException;

class ValidateScoreResult
{

    /**
     * @param array $result
     *
     * @return array
     * @throws InvalidScoreException
     * @throws ResultException
     */
    public function validate(array $result)
    {
        if (!(isset($result['score']) && $this->isScoreValid($result['score']))) {
            throw new InvalidScoreException(
                MessageBuilder::STATUSES[MessageBuilder::STATUS_INVALID_SCORE],
                MessageBuilder::STATUS_INVALID_SCORE,
                null,
                MessageBuilder::build(MessageBuilder::STATUS_INVALID_SCORE, $result)
            );
        }

        if (!isset($result['sourcedId'])) {
            throw new ResultException(
                MessageBuilder::STATUSES[MessageBuilder::STATUS_DELIVERY_EXECUTION_NOT_FOUND],
                MessageBuilder::STATUS_DELIVERY_EXECUTION_NOT_FOUND,
                null,
                MessageBuilder::build(MessageBuilder::STATUS_DELIVERY_EXECUTION_NOT_FOUND, $result)
            );
        }
    }

    /**
     * @param mixed $score
     *
     * @return bool
     */
    private function isScoreValid($score)
    {
        return (is_numeric($score) && $score >= 0 && $score <= 1);
    }

}