<?php
/**
 * CreateController.php
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

namespace FireflyIII\Http\Controllers\Recurring;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RecurrenceFormRequest;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Http\Request;

/**
 *
 * Class CreateController
 */
class CreateController extends Controller
{
    /** @var BudgetRepositoryInterface */
    private $budgets;
    /** @var RecurringRepositoryInterface */
    private $recurring;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', trans('firefly.recurrences'));
                app('view')->share('subTitle', trans('firefly.create_new_recurrence'));

                $this->recurring = app(RecurringRepositoryInterface::class);
                $this->budgets   = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        $budgets           = app('expandedform')->makeSelectListWithEmpty($this->budgets->getActiveBudgets());
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $tomorrow          = new Carbon;
        $oldRepetitionType = $request->old('repetition_type');
        $tomorrow->addDay();

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('recurring.create.fromStore')) {
            $this->rememberPreviousUri('recurring.create.uri');
        }
        $request->session()->forget('recurring.create.fromStore');

        // when will it end?
        $repetitionEnds = [
            'forever'    => trans('firefly.repeat_forever'),
            'until_date' => trans('firefly.repeat_until_date'),
            'times'      => trans('firefly.repeat_times'),
        ];
        // what to do in the weekend?
        $weekendResponses = [
            RecurrenceRepetition::WEEKEND_DO_NOTHING    => trans('firefly.do_nothing'),
            RecurrenceRepetition::WEEKEND_SKIP_CREATION => trans('firefly.skip_transaction'),
            RecurrenceRepetition::WEEKEND_TO_FRIDAY     => trans('firefly.jump_to_friday'),
            RecurrenceRepetition::WEEKEND_TO_MONDAY     => trans('firefly.jump_to_monday'),
        ];

        // flash some data:
        $hasOldInput = null !== $request->old('_token');
        $preFilled   = [
            'first_date'       => $tomorrow->format('Y-m-d'),
            'transaction_type' => $hasOldInput ? $request->old('transaction_type') : 'withdrawal',
            'active'           => $hasOldInput ? (bool)$request->old('active') : true,
            'apply_rules'      => $hasOldInput ? (bool)$request->old('apply_rules') : true,
        ];
        $request->session()->flash('preFilled', $preFilled);

        return view(
            'recurring.create', compact('tomorrow', 'oldRepetitionType', 'weekendResponses', 'preFilled', 'repetitionEnds', 'defaultCurrency', 'budgets')
        );
    }

    /**
     * @param RecurrenceFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function store(RecurrenceFormRequest $request)
    {
        $data       = $request->getAll();
        $recurrence = $this->recurring->store($data);

        $request->session()->flash('success', (string)trans('firefly.stored_new_recurrence', ['title' => $recurrence->title]));
        app('preferences')->mark();

        if (1 === (int)$request->get('create_another')) {
            // set value so create routine will not overwrite URL:
            $request->session()->put('recurring.create.fromStore', true);

            return redirect(route('recurring.create'))->withInput();
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('recurring.create.uri'));

    }

}