<?php
/**
 * Copyright (c) 2021 Heureka Group a.s. All Rights Reserved.
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 *limitations under the License.
 */
namespace PhpRQ\Command\UniqueQueue;

/**
 * @author Jakub Chábek <jakub.chabek@heureka.cz>
 */
class ReEnqueue extends \Predis\Command\ScriptCommand
{

    /**
     * @return int The number of the keys passed to the method as arguments
     */
    protected function getKeysCount()
    {
        return 4;
    }

    /**
     * Gets the body of a Lua script.
     *
     * @return string
     */
    public function getScript()
    {
        return <<<'LUA'
local queue = KEYS[1]
local set = KEYS[2]
local processing = KEYS[3]
local timeouts = KEYS[4]

local item
local inQueue
while true do
    item = redis.call('lpop', processing);

    if not item then
        break
    end

    inQueue = redis.call('sismember', set, item)
    if inQueue == 0 then
        redis.call('rpush', queue, item)
        redis.call('sadd', set, item)
    else
        redis.call('lrem', queue, -1, item)
        redis.call('rpush', queue, item)
    end
end

redis.call('hdel', timeouts, processing)
LUA;
    }

}
