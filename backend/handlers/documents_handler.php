<?php

// backend/handlers/documents_handler.php

global $documents, $applications, $method, $entity, $action, $application_id_param, $input;

if ($entity === 'documents') {
    if ($method === 'GET') {
        if ($application_id_param !== null) { // Get documents for an application: ?entity=documents&application_id=APP002
            $app_docs = [];
            foreach ($documents as $doc) {
                if ($doc['application_id'] == $application_id_param) {
                    $app_docs[] = $doc;
                }
            }
            echo json_encode($app_docs);
        } else {
            http_response_code(400);
            echo json_encode(['error' => "Missing application_id for GET request to documents entity."]);
        }
    } 
    elseif ($method === 'POST') {
        if ($action === 'upload' && $application_id_param !== null) {
            // Expected input: {"student_id": 1, "document_type": "CV", "file_name": "my_cv.pdf"} (file content not handled in mock)
            if (!isset($applications[$application_id_param])) {
                 http_response_code(404);
                 echo json_encode(['error' => "Application {$application_id_param} not found for document upload."]);
                 exit;
            }
            // Only allow document upload for student-confirmed or company-approved applications (example rule)
            if ($applications[$application_id_param]['status'] !== 'Confirmed_By_Student' && $applications[$application_id_param]['status'] !== 'Approved_By_Company') {
                http_response_code(400);
                echo json_encode(['error' => "Documents can only be uploaded for 'Confirmed_By_Student' or 'Approved_By_Company' applications. Application {$application_id_param} status is {$applications[$application_id_param]['status']}."]);
                exit;
            }

            if (!isset($input['student_id']) || !isset($input['document_type']) || !isset($input['file_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing student_id, document_type, or file_name for upload.']);
                exit;
            }
            $new_doc_id = "DOC" . str_pad(count($documents) + 100, 3, "0", STR_PAD_LEFT);
            $new_document = [
                'id' => $new_doc_id,
                'application_id' => $application_id_param,
                'student_id' => $input['student_id'],
                'document_type' => $input['document_type'],
                'file_name' => $input['file_name'],
                'upload_date' => date('Y-m-d'),
                'file_path_mock' => "/uploads/student{$input['student_id']}/{$application_id_param}/{$input['file_name']}" // Mock path
            ];
            $documents[$new_doc_id] = $new_document;
            http_response_code(201);
            echo json_encode($new_document);
        } else {
            http_response_code(400);
            echo json_encode(['error' => "Unknown action or missing application_id for POST request to documents entity."]);
        }
    } 
    else {
        http_response_code(405); // Method Not Allowed for this entity if not GET or POST
        echo json_encode(['error' => "Method $method not allowed for $entity entity."]);
    }
    exit; // Stop further processing
}

?> 