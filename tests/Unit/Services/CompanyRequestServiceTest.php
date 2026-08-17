<?php

namespace Tests\Unit\Services;

use App\Services\CompanyRequestService;
use Tests\TestCase;

class CompanyRequestServiceTest extends TestCase
{
    private CompanyRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CompanyRequestService();
    }

    public function test_registration_form_data_returns_expected_structure_in_english(): void
    {
        app()->setLocale('en');

        $data = $this->service->getRegistrationFormData();

        $this->assertArrayHasKey('form_fields', $data);
        $this->assertIsArray($data['form_fields']);
        $this->assertNotEmpty($data['form_fields']);

        $fieldNames = array_column($data['form_fields'], 'name');
        $this->assertContains('company_name', $fieldNames);
        $this->assertContains('email', $fieldNames);
        $this->assertContains('documents', $fieldNames);
    }

    public function test_registration_form_labels_are_arabic_when_locale_is_ar(): void
    {
        app()->setLocale('ar');

        $data = $this->service->getRegistrationFormData();
        $companyNameField = collect($data['form_fields'])->firstWhere('name', 'company_name');

        $this->assertSame('اسم الشركة', $companyNameField['label']);
    }

    public function test_registration_form_labels_are_english_when_locale_is_en(): void
    {
        app()->setLocale('en');

        $data = $this->service->getRegistrationFormData();
        $companyNameField = collect($data['form_fields'])->firstWhere('name', 'company_name');

        $this->assertSame('Company Name', $companyNameField['label']);
    }

    public function test_foreign_local_field_has_local_and_foreign_options(): void
    {
        app()->setLocale('en');

        $data = $this->service->getRegistrationFormData();
        $typeField = collect($data['form_fields'])->firstWhere('name', 'foreign_local');

        $this->assertSame('select', $typeField['type']);
        $this->assertArrayHasKey('local', $typeField['options']);
        $this->assertArrayHasKey('foreign', $typeField['options']);
    }

    public function test_documents_field_includes_document_types(): void
    {
        app()->setLocale('en');

        $data = $this->service->getRegistrationFormData();
        $documentsField = collect($data['form_fields'])->firstWhere('name', 'documents');

        $this->assertSame('file_uploader', $documentsField['type']);
        $this->assertNotEmpty($documentsField['document_types']);

        $values = array_column($documentsField['document_types'], 'value');
        $this->assertContains('commercial_register', $values);
        $this->assertContains('national_id', $values);
    }

    public function test_sector_field_contains_predefined_sectors(): void
    {
        app()->setLocale('en');

        $data = $this->service->getRegistrationFormData();
        $sectorField = collect($data['form_fields'])->firstWhere('name', 'sector');

        $this->assertGreaterThanOrEqual(10, count($sectorField['options']));
        $values = array_column($sectorField['options'], 'value');
        $this->assertContains('Food Industries', $values);
        $this->assertContains('Industry & Technology', $values);
    }
}
