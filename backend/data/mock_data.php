<?php

// Mock Data for Sabanci Internship Portal

$students = [
    [
        "id" => 1,
        "name" => "John Doe",
        "email" => "student@example.com",
        "student_id" => "20001",
        "major" => "Computer Science",
        "gpa" => "3.5",
        "phone" => "123-456-7890",
        "address" => "123 University Ave, Istanbul",
        "bio" => "A passionate computer science student interested in web development and AI.",
        "profile_pic" => "/assets/images/demo/users/face1.jpg"
    ]
];

$internships = [
    [
        "id" => 101,
        "company_name" => "ABC Technologies",
        "title" => "Software Engineer Intern",
        "location" => "Istanbul, Turkey",
        "dates" => "June 2024 - August 2024",
        "description" => "Work on exciting projects in the web development team. Required skills: PHP, JavaScript, HTML, CSS."
    ],
    [
        "id" => 102,
        "company_name" => "Global Innovations",
        "title" => "Data Analyst Intern",
        "location" => "Remote",
        "dates" => "July 2024 - September 2024",
        "description" => "Analyze large datasets and generate reports. Required skills: Python, SQL, Tableau."
    ]
];

$applications = [
    [
        "application_id" => 1001,
        "student_id" => 1,
        "internship_id" => 102,
        "status" => "Pending", // Pending, Approved, Rejected, Withdrawn, Accepted
        "application_date" => "2024-10-01"
    ]
];

$documents = [
    [
        "document_id" => 2001,
        "student_id" => 1,
        "document_type" => "CV",
        "file_name" => "JohnDoe_CV.pdf",
        "upload_date" => "2024-09-28"
    ],
    [
        "document_id" => 2002,
        "student_id" => 1,
        "document_type" => "Transcript",
        "file_name" => "Transcript_JohnDoe.pdf",
        "upload_date" => "2024-09-28"
    ]
];

$terms = [
    ["id" => 1, "name" => "2023-2024 Fall", "start_date" => "2023-09-15", "end_date" => "2024-01-15"],
    ["id" => 2, "name" => "2023-2024 Spring", "start_date" => "2024-02-01", "end_date" => "2024-06-15"],
    ["id" => 3, "name" => "2024-2025 Fall", "start_date" => "2024-09-15", "end_date" => "2025-01-15"]
];

$companies = [
    [
        "id" => 1,
        "name" => "ABC Technologies",
        "email" => "company@example.com",
        "industry" => "Information Technology",
        "website" => "http://abctech.example.com",
        "phone" => "987-654-3210",
        "address" => "456 Tech Park, Istanbul"
    ],
    [
        "id" => 2,
        "name" => "Global Innovations",
        "email" => "innovate@example.com",
        "industry" => "Research & Development",
        "website" => "http://globalinnovations.example.com",
        "phone" => "876-543-2109",
        "address" => "789 Innovation Hub, Remote"
    ]
];

?>