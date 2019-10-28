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

namespace Erede\Domain\Pagamento\Responses;


use CheckoutDLX\Domain\Responses\ValueObjects\Retorno;
use DateTime;
use Exception;
use stdClass;

class CapturaResponse extends \CheckoutDLX\Domain\Responses\CapturaResponse
{
    /**
     * @param stdClass $std_class
     * @return CapturaResponse
     * @throws Exception
     */
    public static function createFormStd(stdClass $std_class): self
    {
        // Em alguns casos, o retorno não é enviado por essa classe, pois o mesmo está relacionado a requisição
        // e não a entidade em si
        $returnCode = property_exists($std_class, 'returnCode') ? $std_class->returnCode : null;
        $returnMessage = property_exists($std_class, 'returnMessage') ? $std_class->returnMessage : '';

        $retorno = new Retorno($returnCode, $returnMessage);

        return new self(
            $std_class->reference ?? null,
            $std_class->tid ?? null,
            $std_class->nsu,
            new DateTime($std_class->dateTime),
            $retorno
        );
    }
}