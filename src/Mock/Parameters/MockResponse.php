<?php
/*
 * This file is part of Swagger Mock.
 *
 * (c) Igor Lazarev <strider2038@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Mock\Parameters;

use App\Mock\Parameters\Schema\SchemaCollection;
use App\OpenAPI\SpecificationObjectMarkerInterface;

/**
 * @author Igor Lazarev <strider2038@yandex.ru>
 */
class MockResponse implements SpecificationObjectMarkerInterface
{
    /** @var int */
    public $statusCode;

    /** @var SchemaCollection */
    public $content;

    public function __construct()
    {
        $this->content = new SchemaCollection();
    }
}
