<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(RefreshDatabase::class)->in('Feature/Database');
uses(RefreshDatabase::class)->in('Feature/Api');
uses(RefreshDatabase::class)->in('Feature/Auth');
uses(RefreshDatabase::class)->in('Feature/Geo');
uses(RefreshDatabase::class)->in('Feature/Company');
uses(RefreshDatabase::class)->in('Feature/Moderation');
uses(RefreshDatabase::class)->in('Feature/Filament');
uses(RefreshDatabase::class)->in('Feature/Import');
uses(RefreshDatabase::class)->in('Feature/Content');
uses(RefreshDatabase::class)->in('Feature/Ai');
uses(RefreshDatabase::class)->in('Feature/Search');
uses(RefreshDatabase::class)->in('Feature/Seo');
uses(RefreshDatabase::class)->in('Feature/Billing');
uses(RefreshDatabase::class)->in('Feature/Console');
