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

namespace Erede\Tests\Application\Pagamento\Services;

use CheckoutDLX\Domain\Requests\AutorizacaoRequest;
use CheckoutDLX\Domain\Requests\CancelamentoRequest;
use CheckoutDLX\Domain\Requests\CapturaRequest;
use CheckoutDLX\Domain\Requests\ConsultaRequest;
use CheckoutDLX\Domain\Requests\ValueObjects\Cartao;
use Erede\Application\Pagamento\Services\GatewayErede;
use Erede\Domain\Pagamento\Responses\AutorizacaoResponse;
use Erede\Domain\Pagamento\Responses\CancelamentoResponse;
use Erede\Domain\Pagamento\Responses\CapturaResponse;
use Erede\Domain\Pagamento\Responses\ConsultaResponse;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;

/**
 * Class GatewayEredeTest
 * @package Erede\Tests\Application\Pagamento\Services
 * @coversDefaultClass \Erede\Application\Pagamento\Services\GatewayErede
 */
class GatewayEredeTest extends TestCase
{
    /**
     * @return GatewayErede
     * @throws ReflectionException
     */
    public function test__construct(): GatewayErede
    {
        $numero_afiliacao = '10000167'; // PV Loja de Teste
        $token = '0c1bc5ff872a43f5b33a2a8a8ffe1361'; // Token da loja de teste

        $erede = new GatewayErede($numero_afiliacao, $token, 'sandbox');

        $rfx_numero_afiliacao = new ReflectionProperty($erede, 'numero_afiliacao');
        $rfx_numero_afiliacao->setAccessible(true);

        $this->assertInstanceOf(GatewayErede::class, $erede);
        $this->assertEquals($numero_afiliacao, $rfx_numero_afiliacao->getValue($erede));

        return $erede;
    }

    /**
     * @param GatewayErede $erede
     * @covers ::getNomeServico
     * @depends test__construct
     */
    public function test_GetNomeServico_deve_retornar_o_valor_da_constante_NOME_SERVICO(GatewayErede $erede)
    {
        $this->assertEquals($erede::NOME_SERVICO, $erede->getNomeServico());
    }

    /**
     * @covers ::autorizar
     * @depends test__construct
     * @param GatewayErede $erede
     * @return AutorizacaoResponse
     * @throws Exception
     */
    public function test_Autorizar_deve_enviar_uma_requisicao_de_autorizacao_para_erede_sem_captura_automatica(GatewayErede $erede): AutorizacaoResponse
    {
        $cartao = new Cartao(
            'TESTE UNITARIO',
            '5448280000000007',
            12,
            2020,
            '123'
        );

        $autorizacao_request = new AutorizacaoRequest(
            false,
            'credit',
            mt_rand(),
            1234.00,
            1,
            $cartao
        );

        /** @var AutorizacaoResponse $autorizacao_response */
        $autorizacao_response = $erede->autorizar($autorizacao_request);

        $this->assertInstanceOf(AutorizacaoResponse::class, $autorizacao_response);
        $this->assertEquals('00', $autorizacao_response->getRetorno()->getCodigo());

        return $autorizacao_response;
    }

    /**
     * @param GatewayErede $erede
     * @param AutorizacaoResponse $autorizacao_response
     * @throws Exception
     * @covers ::capturar
     * @depends test__construct
     * @depends test_Autorizar_deve_enviar_uma_requisicao_de_autorizacao_para_erede_sem_captura_automatica
     */
    public function test_Capturar_deve_capturar_o_valor_total_da_transacao(GatewayErede $erede, AutorizacaoResponse $autorizacao_response)
    {
        $captura_request = new CapturaRequest($autorizacao_response->getTransacaoId(), $autorizacao_response->getValor());

        /** @var CapturaResponse $captura_response */
        $captura_response = $erede->capturar($captura_request);

        $this->assertInstanceOf(CapturaResponse::class, $captura_response);
        $this->assertEquals('00', $captura_response->getRetorno()->getCodigo());
    }

    /**
     * @covers ::capturar
     * @param GatewayErede $erede
     * @param AutorizacaoResponse $autorizacao_response
     * @throws Exception
     * @depends test__construct
     * @depends test_Autorizar_deve_enviar_uma_requisicao_de_autorizacao_para_erede_sem_captura_automatica
     */
    public function test_Consultar_deve_consultar_uma_transacao_na_erede(GatewayErede $erede, AutorizacaoResponse $autorizacao_response)
    {
        $consulta_request = new ConsultaRequest($autorizacao_response->getTransacaoId());

        $consulta_response = $erede->consultar($consulta_request);

        $this->assertInstanceOf(ConsultaResponse::class, $consulta_response);
    }

    /**
     * @covers ::cancelar
     * @param GatewayErede $erede
     * @param AutorizacaoResponse $autorizacao_response
     * @depends test__construct
     * @depends test_Autorizar_deve_enviar_uma_requisicao_de_autorizacao_para_erede_sem_captura_automatica
     * @throws Exception
     */
    public function test_Cancelar_deve_gerar_reembolso_do_valor_total_de_uma_determiada_transacao(GatewayErede $erede, AutorizacaoResponse $autorizacao_response)
    {
        $cancelar_request = new CancelamentoRequest($autorizacao_response->getTransacaoId(), $autorizacao_response->getValor());

        /** @var CancelamentoResponse $cancelar_response */
        $cancelar_response = $erede->cancelar($cancelar_request);

        $this->assertInstanceOf(CancelamentoResponse::class, $cancelar_response);
        $this->assertNotEmpty($cancelar_response->getCancelamentoId());
    }
}
