<?php

namespace tests\Lenken;

use PhpSpec\ObjectBehavior;

class MarkdownSpec extends ObjectBehavior
{
    function it_converts_plain_text_to_html_paragraphs()
    {
        $this->toHtml("Hi, there")->shouldReturn("<p>Hi, there</p>");
    }
}
