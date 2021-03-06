<?php
/**
 * AutomationHandler.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Handlers\Events;

use Exception;
use FireflyIII\Events\RequestedReportOnJournals;
use FireflyIII\Mail\ReportNewJournalsMail;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mail;

/**
 * Class AutomationHandler
 */
class AutomationHandler
{

    /**
     * @param RequestedReportOnJournals $event
     *
     * @return bool
     */
    public function reportJournals(RequestedReportOnJournals $event): bool
    {
        Log::debug('In reportJournals.');
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->findNull($event->userId);
        if (null === $user) {
            Log::debug('User is NULL');

            return true;
        }
        if ($event->journals->count() === 0) {
            Log::debug('No journals.');

            return true;
        }

        try {
            Log::debug('Trying to mail...');
            Mail::to($user->email)->send(new ReportNewJournalsMail($user->email, '127.0.0.1', $event->journals));
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
        Log::debug('Done!');

        // @codeCoverageIgnoreEnd
        return true;
    }
}