<?php

return [
    'title' => 'Documents',

    'navigation' => [
        'title' => 'Documents',
        'group' => null,
    ],

    'singular' => 'Document',
    'plural'   => 'Documents',

    'form' => [
        'sections' => [
            'basic_information' => [
                'title' => 'Basic Information',

                'fields' => [
                    'title'       => 'Document Title',
                    'source_type' => 'Source Type',
                ],
            ],
            'content' => [
                'title' => 'Content',

                'fields' => [
                    'docx_file'    => 'DOCX File',
                    'html_content' => 'HTML Content',
                ],
            ],
        ],

        'placeholders' => [
            'title' => 'Enter a document title',
        ],
    ],

    'actions' => [
        'download_excel_template' => 'Download Excel Template',
        'download_word'           => 'Download Word Document',
    ],

    'helpers' => [
        'filename' => 'Leave blank to use the document title, or use {{$KEY}} placeholders to build a dynamic filename.',
        'excel'    => 'Use the generated template with placeholder keys as headers. Each row will produce one document, and multiple rows will be bundled into a ZIP file.',
    ],

    'placeholders' => [
        'filename' => 'Example: agreement-{{$NAME}}',
    ],

    'fields' => [
        'title'        => 'Document Title',
        'source_type'  => 'Source Type',
        'docx_file'    => 'DOCX File',
        'html_content' => 'HTML Content',
        'filename'     => 'Filename',
        'mode'         => 'Mode',
        'key_value'    => 'Placeholder Values',
        'key'          => 'Key',
        'value'        => 'Value',
        'upload_excel' => 'Upload Excel File',
        'created_at'   => 'Created At',
        'updated_at'   => 'Updated At',
    ],

    'options' => [
        'source_type' => [
            'html' => 'HTML',
            'docx' => 'DOCX',
        ],
        'mode' => [
            'single' => 'Single Document',
            'bulk'   => 'Bulk from Excel',
        ],
    ],

    'table' => [
        'columns' => [
            'title'       => 'Document Title',
            'source_type' => 'Source Type',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
        ],
    ],

    'notifications' => [
        'template_error' => [
            'title' => 'Unable to create the Excel template',
        ],
        'placeholder_error' => [
            'title' => 'Unable to detect document placeholders',
        ],
        'download_error' => [
            'title' => 'Unable to download the document',
        ],
        'docx_missing' => [
            'title' => 'The DOCX file could not be found',
        ],
    ],

    'messages' => [
        'docx_missing'         => 'The DOCX file could not be found.',
        'bulk_excel_required'  => 'Bulk mode requires an Excel file.',
        'excel_missing'        => 'The Excel file could not be found on the local disk.',
        'excel_empty'          => 'No data rows were found. Please add values starting from row 2.',
        'zip_failed'           => 'Unable to create the ZIP archive.',
    ],
];
