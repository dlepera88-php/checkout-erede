<?php
/**
 * MIT License
 *
 * Copyright (c) 2018 PHP DLX
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Erede\Application\Pagamento\Services;


class URL
{
    const AUTORIZACAO = [
        'producao' => [
            'metodo' => 'POST',
            'url' => 'https://api.userede.com.br/erede/v1/transactions'
        ],

        'sandbox' => [
            'metodo' => 'POST',
            'url' => 'https://api.userede.com.br/desenvolvedores/v1/transactions'
        ]
    ];

    const CAPTURA = [
        'producao' => [
            'metodo' => 'PUT',
            'url' => 'https://api.userede.com.br/erede/v1/transactions/%s'
        ],

        'sandbox' => [
            'metodo' => 'PUT',
            'url' => 'https://api.userede.com.br/desenvolvedores/v1/transactions/%s'
        ]
    ];

    const CONSULTA = [
        'producao' => [
            'metodo' => 'GET',
            'url' => 'https://api.userede.com.br/erede/v1/transactions/%s'
        ],

        'sandbox' => [
            'metodo' => 'GET',
            'url' => 'https://api.userede.com.br/desenvolvedores/v1/transactions/%s'
        ]
    ];

    const CANCELAMENTO = [
        'producao' => [
            'metodo' => 'POST',
            'url' => 'https://api.userede.com.br/erede/v1/transactions/%s/refunds'
        ],

        'sandbox' => [
            'metodo' => 'POST',
            'url' => 'https://api.userede.com.br/desenvolvedores/v1/transactions/%s/refunds'
        ]
    ];
}