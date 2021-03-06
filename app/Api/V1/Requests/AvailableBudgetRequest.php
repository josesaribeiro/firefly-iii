<?php
/**
 * AvailableBudgetRequest.php
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

namespace FireflyIII\Api\V1\Requests;

/**
 * Class AvailableBudgetRequest
 */
class AvailableBudgetRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return [
            'transaction_currency_id' => $this->integer('transaction_currency_id'),
            'amount'                  => $this->string('amount'),
            'start_date'              => $this->date('start_date'),
            'end_date'                => $this->date('end_date'),
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'transaction_currency_id' => 'required|numeric|exists:transaction_currencies,id',
            'amount'                  => 'required|numeric|more:0',
            'start_date'              => 'required|date|before:end_date',
            'end_date'                => 'required|date|after:start_date',
        ];

        return $rules;
    }


}