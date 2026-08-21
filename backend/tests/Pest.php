<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(RefreshDatabase::class)->in('Feature/Database');
uses(RefreshDatabase::class)->in('Feature/Api');
uses(RefreshDatabase::class)->in('Feature/Auth');
