<?php
/*
 * This file is part of Swagger Mock.
 *
 * (c) Igor Lazarev <strider2038@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Mock;

use App\API\Responder;
use App\Mock\Generation\DataGenerator;
use App\Mock\Negotiation\MediaTypeNegotiator;
use App\Mock\Negotiation\ResponseStatusNegotiator;
use App\Mock\Parameters\Endpoint;
use App\Mock\Parameters\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Igor Lazarev <strider2038@yandex.ru>
 */
class MockResponseGenerator
{
    /** @var MediaTypeNegotiator */
    private $mediaTypeNegotiator;

    /** @var ResponseStatusNegotiator */
    private $responseStatusNegotiator;

    /** @var DataGenerator */
    private $dataGenerator;

    /** @var Responder */
    private $responder;

    public function __construct(
        MediaTypeNegotiator $mediaTypeNegotiator,
        ResponseStatusNegotiator $responseStatusNegotiator,
        DataGenerator $dataGenerator,
        Responder $responder
    ) {
        $this->mediaTypeNegotiator = $mediaTypeNegotiator;
        $this->responseStatusNegotiator = $responseStatusNegotiator;
        $this->dataGenerator = $dataGenerator;
        $this->responder = $responder;
    }

    public function generateResponse(Request $request, Endpoint $parameters): Response
    {
        $statusCode = $this->responseStatusNegotiator->negotiateResponseStatus($request, $parameters);
        /** @var MockResponse $mockResponse */
        $mockResponse = $parameters->responses->get($statusCode);
        $mediaType = $this->mediaTypeNegotiator->negotiateMediaType($request, $mockResponse);
        $schema = $mockResponse->content->get($mediaType);

        if (null === $schema) {
            $responseData = '';
        } else {
            $responseData = $this->dataGenerator->generateData($schema);
        }

        return $this->responder->createResponse($statusCode, $mediaType, $responseData);
    }
}
