<?php

declare(strict_types=1);

namespace App\Enums;

enum AiProvider: string
{
    case OpenAiBatch = 'openai_batch';
    case LocalLlm = 'local_llm';
    case Anthropic = 'anthropic';
}
