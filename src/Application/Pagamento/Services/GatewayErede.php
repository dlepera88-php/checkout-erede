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


use CheckoutDLX\Domain\Requests\AutorizacaoRequest;
use CheckoutDLX\Domain\Requests\CancelamentoRequest;
use CheckoutDLX\Domain\Requests\CapturaRequest;
use CheckoutDLX\Domain\Requests\ConsultaRequest;
use CheckoutDLX\Domain\Responses\AutorizacaoResponse;
use CheckoutDLX\Domain\Responses\CancelamentoResponse;
use CheckoutDLX\Domain\Responses\CapturaResponse;
use CheckoutDLX\Domain\Responses\ConsultaResponse;
use CheckoutDLX\Domain\Services\Pagamento\GatewayPagamentoInterface;
use Erede\Domain\Pagamento\Responses as Erede;
use Exception;
use stdClass;

/**
 * Class GatewayErede
 * @package Erede\Application\Pagamento\Services
 * @covers GatewayEredeTest
 */
class GatewayErede implements GatewayPagamentoInterface
{
    /**
     * @var int
     */
    private $numero_afiliacao;
    /**
     * @var string
     */
    private $token;
    /**
     * @var string
     */
    private $ambiente;

    /**
     * GatewayErede constructor.
     * @param int $numero_afiliacao
     * @param string $token
     * @param string $ambiente
     */
    public function __construct(int $numero_afiliacao, string $token, string $ambiente = 'producao')
    {
        $this->numero_afiliacao = $numero_afiliacao;
        $this->token = $token;
        $this->ambiente = $ambiente;
    }

    /**
     * Enviar requisição de autorização para o gateway de pagamento.
     * @param AutorizacaoRequest $request
     * @return AutorizacaoResponse
     * @throws Exception
     */
    public function autorizar(AutorizacaoRequest $request): AutorizacaoResponse
    {
        $params = [
            'capture' => $request->isCapturaAutomatica(),
            'kind' => $request->getTipoTransacao(),
            'reference' => $request->getReferencia(),
            'amount' => intval($request->getValor() * 100),
            'installments' => $request->getParcelas(),
            'cardHolderName' => $request->getCartao()->getDonoCartao(),
            'cardNumber' => $request->getCartao()->getNumeroCartao(),
            'expirationMonth' => $request->getCartao()->getExpiracaoMes(),
            'expirationYear' => $request->getCartao()->getExpiracaoAno(),
            'securityCode' => $request->getCartao()->getCodigoSeguranca(),
            'softDescriptor' => $request->getDescricaoFatura(),
            'subscription' => $request->isRecorrente(),
            'origin' => 1, // e-Rede
            'distributorAffiliation' => $this->numero_afiliacao
        ];

        $metodo = URL::AUTORIZACAO[$this->ambiente]['metodo'];
        $url = URL::AUTORIZACAO[$this->ambiente]['url'];

        $retorno = $this->enviarRequisicao($metodo, $url, $params);

        return Erede\AutorizacaoResponse::createFromStd($retorno);
    }

    /**
     * Capturar uma determinada transação.
     * Essa opção será utilizada apenas se a autorização não for configurada como ˜captura automática˜.
     * @param CapturaRequest $request
     * @return CapturaResponse
     * @throws Exception
     */
    public function capturar(CapturaRequest $request): CapturaResponse
    {
        $params = [
            'amount' => $request->getValor() * 100
        ];

        $metodo = URL::CAPTURA[$this->ambiente]['metodo'];
        $url = sprintf(URL::CAPTURA[$this->ambiente]['url'], $request->getTransacaoId());

        $retorno = $this->enviarRequisicao($metodo, $url, $params);

        return Erede\CapturaResponse::createFormStd($retorno);
    }

    /**
     * Consultar uma transação.
     * @param ConsultaRequest $request
     * @return ConsultaResponse
     * @throws Exception
     */
    public function consultar(ConsultaRequest $request): ConsultaResponse
    {
        $params = [
            'tid' => $request->getTransacaoId()
        ];

        $metodo = URL::CONSULTA[$this->ambiente]['metodo'];
        $url = sprintf(URL::CONSULTA[$this->ambiente]['url'], $request->getTransacaoId());

        $retorno = $this->enviarRequisicao($metodo, $url, $params);

        return Erede\ConsultaResponse::createFromStd($retorno);
    }

    /**
     * Cancelar e gerar reembolso de uma determinada transação.
     * @param CancelamentoRequest $request
     * @return CancelamentoResponse
     * @throws Exception
     */
    public function cancelar(CancelamentoRequest $request): CancelamentoResponse
    {
        $params = [
            'amount' => $request->getValor() * 100
        ];

        $metodo = URL::CANCELAMENTO[$this->ambiente]['metodo'];
        $url = sprintf(URL::CANCELAMENTO[$this->ambiente]['url'], $request->getTransacaoId());

        $retorno = $this->enviarRequisicao($metodo, $url, $params);

        return Erede\CancelamentoResponse::createFromStd($retorno);
    }

    /**
     * Enviar requisição para o servidor da e-Rede
     * @param string $metodo Método de envio
     * @param string $url URL para enviar as informações
     * @param array $params Parâmetros a serem enviados para a requisição
     * @return stdClass
     */
    private function enviarRequisicao(string $metodo, string $url, array $params): stdClass
    {
        $params_json = json_encode($params);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $metodo);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params_json);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . $this->getAutenticacao(),
            'Content-Length: ' . strlen($params_json)
        ]);

        $json = json_decode(curl_exec($curl));
        curl_close($curl);

        return $json;
    }

    /**
     * Retorna a hash bse64 referente a autenticação do e.Rede
     * @return string
     */
    private function getAutenticacao(): string
    {
        return base64_encode("{$this->numero_afiliacao}:{$this->token}");
    }
}