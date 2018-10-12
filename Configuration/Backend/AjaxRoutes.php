<?php

return [
    'hugo_admininistator_export' => [
        'path' => '/hugo/admininistrator/export',
        'target' => \SourceBroker\Hugo\Controller\AdministrationController::class.'::exportAjax',
    ]
];
