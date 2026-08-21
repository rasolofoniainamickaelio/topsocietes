<?php

declare(strict_types=1);

namespace App\Domain\Ai\Enums;

enum AiProvider: string
{
    case OpenAiBatch = 'openai_batch';
    case LocalLlm = 'local_llm';
    case Anthropic = 'anthropic';
}
