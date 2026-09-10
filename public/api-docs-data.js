const apiData = [
    {
        "category": "LOGIN",
        "endpoints": [
            {
                "id": "9d4135e8d28957eda0bbc8bb215d8fda",
                "method": "POST",
                "path": "\/api\/login",
                "name": "Admin login API",
                "description": "",
                "params": [
                    {
                        "name": "email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "password",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "CLIENT",
        "endpoints": [
            {
                "id": "6b39a31ed8efc71b5927e6fe475f9526",
                "method": "POST",
                "path": "\/api\/client\/register",
                "name": "Client Register API",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "company_name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "password",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "08ee81ff505a4b5740dddc96e9f35d6c",
                "method": "POST",
                "path": "\/api\/client\/login",
                "name": "Client Login API",
                "description": "",
                "params": [
                    {
                        "name": "email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "password",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "bb1c220d5b4042dde0744869f3f4977b",
                "method": "POST",
                "path": "\/api\/client\/forgot-password",
                "name": "Forgot Password API (Send OTP)",
                "description": "",
                "params": [
                    {
                        "name": "email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "f9e820b671ec3ca942cdd9240bf7dec0",
                "method": "POST",
                "path": "\/api\/client\/verify-otp",
                "name": "Verify OTP API",
                "description": "",
                "params": [
                    {
                        "name": "email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "otp",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "ed85350b076e1247acedf0628034eba9",
                "method": "POST",
                "path": "\/api\/client\/reset-password",
                "name": "Reset Password API (Using OTP)",
                "description": "",
                "params": [
                    {
                        "name": "email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "otp",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "password",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "c9848fa431bc9ddeb13bf1946a2a715d",
                "method": "POST",
                "path": "\/api\/client\/logout",
                "name": "Client Logout API",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "e202df3f4d8e6fcca55aab33e1f35839",
                "method": "GET",
                "path": "\/api\/client\/profile",
                "name": "Get Client Profile API",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "8d94c7ef3a722e497539f96d367bf40a",
                "method": "POST",
                "path": "\/api\/client\/profile",
                "name": "Update Client Profile API",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "text",
                        "required": true,
                        "description": "Must not be greater than 255 characters."
                    },
                    {
                        "name": "contact",
                        "type": "text",
                        "required": true,
                        "description": "Must not be greater than 20 characters."
                    },
                    {
                        "name": "profile_image",
                        "type": "file",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "02abd5e3f24b3eefafe3fd4f78470177",
                "method": "POST",
                "path": "\/api\/client\/change-password",
                "name": "Change Password API",
                "description": "",
                "params": [
                    {
                        "name": "current_password",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "new_password",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "b10fd572c45c7e7e1cd18f435f64a264",
                "method": "GET",
                "path": "\/api\/client\/purchases",
                "name": "Display a listing of all purchases (tenants) owned by the logged-in client.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "5579f65d1a0d87e83884beab25696c30",
                "method": "POST",
                "path": "\/api\/client\/purchases",
                "name": "POST api\/client\/purchases",
                "description": "",
                "params": [
                    {
                        "name": "business_name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "primary_contact_email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "phone_number",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "industry",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "address",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "product_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "plan_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "domain_type",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "domain",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "transaction_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "amount",
                        "type": "double",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "currency",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "payment_method",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "payment_status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "4dcd89462cacefe11a802fade6bd3eef",
                "method": "GET",
                "path": "\/api\/client\/purchases\/:uuid",
                "name": "Display the detailed information of a specific purchase (tenant) owned by the client.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "b3af456a880a0babe6ce7916748fd5dc",
                "method": "GET",
                "path": "\/api\/client\/payments",
                "name": "Display a listing of all payments made by this client.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            }
        ]
    },
    {
        "category": "LOGOUT",
        "endpoints": [
            {
                "id": "cab0d889bf21c5c052f8187d06a7376e",
                "method": "POST",
                "path": "\/api\/logout",
                "name": "Logout API",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "CHANGE-PASSWORD",
        "endpoints": [
            {
                "id": "fb9a008096556c40f73263d100875a13",
                "method": "POST",
                "path": "\/api\/change-password",
                "name": "Change Password API",
                "description": "",
                "params": [
                    {
                        "name": "current_password",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "new_password",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "PROFILE",
        "endpoints": [
            {
                "id": "1c1ccbc80bc38d88a934661a772bf000",
                "method": "GET",
                "path": "\/api\/profile",
                "name": "Get Admin Profile API",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "944a7e20062d13241319853f33c61374",
                "method": "POST",
                "path": "\/api\/profile",
                "name": "Update Admin Profile API",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "text",
                        "required": true,
                        "description": "Must not be greater than 255 characters."
                    },
                    {
                        "name": "contact",
                        "type": "text",
                        "required": true,
                        "description": "Must not be greater than 20 characters."
                    },
                    {
                        "name": "profile_image",
                        "type": "file",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "PRODUCT-CATEGORIES",
        "endpoints": [
            {
                "id": "e1b2496be922bc8b5e469ea1d112abd8",
                "method": "POST",
                "path": "\/api\/product-categories\/:product_category_id",
                "name": "Update the specified resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "text",
                        "required": true,
                        "description": "Must not be greater than 255 characters."
                    },
                    {
                        "name": "description",
                        "type": "text",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "is_active",
                        "type": "text",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "image",
                        "type": "file",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "7498cc6afe44676a62ed5c8c617f4ab6",
                "method": "GET",
                "path": "\/api\/product-categories",
                "name": "Display a listing of the resource.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "38f38471bbc14280fbef6cc268f2c3aa",
                "method": "POST",
                "path": "\/api\/product-categories",
                "name": "Store a newly created resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "text",
                        "required": true,
                        "description": "Must not be greater than 255 characters."
                    },
                    {
                        "name": "description",
                        "type": "text",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "is_active",
                        "type": "text",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "image",
                        "type": "file",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "0f3a01bb3aee1e01b87c6a9ea08f06e6",
                "method": "GET",
                "path": "\/api\/product-categories\/:id",
                "name": "Display the specified resource.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "f2e45f802a4b11f4b9128c68c84da1b4",
                "method": "POST",
                "path": "\/api\/product-categories\/:id",
                "name": "Update the specified resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "text",
                        "required": true,
                        "description": "Must not be greater than 255 characters."
                    },
                    {
                        "name": "description",
                        "type": "text",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "is_active",
                        "type": "text",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "image",
                        "type": "file",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "_method",
                        "type": "text",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "c6cc862180348335696d744feae88c21",
                "method": "DELETE",
                "path": "\/api\/product-categories\/:id",
                "name": "Remove the specified resource from storage.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "SETTINGS",
        "endpoints": [
            {
                "id": "b3e6679ec876561e3233b39db529969c",
                "method": "GET",
                "path": "\/api\/settings",
                "name": "Retrieve all settings.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "a15f100dd12f772dba6eb44bb6675033",
                "method": "POST",
                "path": "\/api\/settings",
                "name": "Insert or update settings.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "f11d1aabe4f87951a7792c709a9380e1",
                "method": "GET",
                "path": "\/api\/settings\/smtp",
                "name": "Retrieve SMTP settings.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "33dac96fa1c40675ce2e4acd70a0f90d",
                "method": "POST",
                "path": "\/api\/settings\/smtp",
                "name": "Insert or update SMTP settings specifically.",
                "description": "",
                "params": [
                    {
                        "name": "mail_mailer",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "mail_host",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "mail_port",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "mail_username",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "mail_password",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "mail_encryption",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "mail_from_address",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "mail_from_name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "ba8cada6b5ed23097f57ab154e997372",
                "method": "GET",
                "path": "\/api\/settings\/general",
                "name": "Retrieve General settings.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "dd201ab9981620804e744c5a42d71c76",
                "method": "POST",
                "path": "\/api\/settings\/general",
                "name": "Insert or update General settings.",
                "description": "",
                "params": [
                    {
                        "name": "company_name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "company_phone",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "company_email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "company_address",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "company_gst",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "company_tagline",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "admin_login_mail_send",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "04c63700631654953e6a174b0a2ea51f",
                "method": "GET",
                "path": "\/api\/settings\/payment-methods",
                "name": "GET api\/settings\/payment-methods",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "1fb5e15e6a91579a31a6e89b7f40e60c",
                "method": "POST",
                "path": "\/api\/settings\/payment-methods",
                "name": "POST api\/settings\/payment-methods",
                "description": "",
                "params": [
                    {
                        "name": "razorpay_key_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "razorpay_key_secret",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "razorpay_active",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "AUDIT-LOGS",
        "endpoints": [
            {
                "id": "8682bc9b1b827c26167c86f0d67c0e8c",
                "method": "GET",
                "path": "\/api\/audit-logs",
                "name": "Retrieve audit logs with filtering and pagination.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            }
        ]
    },
    {
        "category": "PLANS",
        "endpoints": [
            {
                "id": "865addc352ed99106671bb779ee25133",
                "method": "POST",
                "path": "\/api\/plans\/:plan_id",
                "name": "Update the specified resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "billing_cycle",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "is_active",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "is_popular",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "max_users",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "max_orders",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "additional_order_price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "store_configuration",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_hybrid_customer_app",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_hybrid_customer_merchant_app",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_unlimited_users_listings",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_white_labeled_solution",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_white_labeled_dashboard",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "storage_gb",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "duration_days",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "2781b7c3891ca87c7476e72234e54192",
                "method": "GET",
                "path": "\/api\/plans",
                "name": "Display a listing of the resource.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "89361f61d16d2e68d03f16c4315e0e0a",
                "method": "POST",
                "path": "\/api\/plans",
                "name": "Store a newly created resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "product_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "billing_cycle",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "is_active",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "is_popular",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "max_users",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "max_orders",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "additional_order_price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "store_configuration",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_hybrid_customer_app",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_hybrid_customer_merchant_app",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_unlimited_users_listings",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_white_labeled_solution",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_white_labeled_dashboard",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "storage_gb",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "duration_days",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "34dbd593bdc73cffc1101ec5d5c9d692",
                "method": "GET",
                "path": "\/api\/plans\/:id",
                "name": "Display the specified resource.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "a6f5fcc065248e453c93b7f8ea49725f",
                "method": "PUT",
                "path": "\/api\/plans\/:id",
                "name": "Update the specified resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "billing_cycle",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "is_active",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "is_popular",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "max_users",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "max_orders",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "additional_order_price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "store_configuration",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_hybrid_customer_app",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_hybrid_customer_merchant_app",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_unlimited_users_listings",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_white_labeled_solution",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "has_white_labeled_dashboard",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "storage_gb",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "duration_days",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "321c4439bf58434be7921a2db20240aa",
                "method": "DELETE",
                "path": "\/api\/plans\/:id",
                "name": "Remove the specified resource from storage.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "PRODUCTS",
        "endpoints": [
            {
                "id": "dfcfdf78718829584f8922bad9c3943f",
                "method": "POST",
                "path": "\/api\/products\/:product_id",
                "name": "Update the specified resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "tagline",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "starting_price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "badge",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "fede97d6dba436c52b616f0debc373f3",
                "method": "GET",
                "path": "\/api\/products",
                "name": "Display a listing of the resource.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "f683e41d4b9e9c8102c783cc44bb680f",
                "method": "POST",
                "path": "\/api\/products",
                "name": "Store a newly created resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "text",
                        "required": true,
                        "description": "Must not be greater than 255 characters."
                    },
                    {
                        "name": "tagline",
                        "type": "text",
                        "required": true,
                        "description": "Must not be greater than 255 characters."
                    },
                    {
                        "name": "starting_price",
                        "type": "text",
                        "required": true,
                        "description": "Must be at least 0."
                    },
                    {
                        "name": "badge",
                        "type": "text",
                        "required": true,
                        "description": "Must not be greater than 255 characters."
                    },
                    {
                        "name": "description",
                        "type": "text",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "images[]",
                        "type": "file",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "630340417c54f22491d5612849c9d01b",
                "method": "GET",
                "path": "\/api\/products\/:id",
                "name": "Display the specified resource.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "9088168a85c5f7fae5772d1b691eabc5",
                "method": "PUT",
                "path": "\/api\/products\/:id",
                "name": "Update the specified resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "tagline",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "starting_price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "badge",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "f322b9fde492304defcb14d92a8e7eb0",
                "method": "DELETE",
                "path": "\/api\/products\/:id",
                "name": "Remove the specified resource from storage.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "ADD-ONS",
        "endpoints": [
            {
                "id": "6e56141ee45f5af80a707244325b5a5b",
                "method": "POST",
                "path": "\/api\/add-ons\/:add_on_id",
                "name": "Update the specified resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "period",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "limit",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "95f4ec232f4869324d2062d62ef58a01",
                "method": "GET",
                "path": "\/api\/add-ons",
                "name": "Display a listing of the resource.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "e0185c60de65a7a623ed2367d2d26d40",
                "method": "POST",
                "path": "\/api\/add-ons",
                "name": "Store a newly created resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "period",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "limit",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "065f3c67bf54e7eb2445b7c50f653e69",
                "method": "GET",
                "path": "\/api\/add-ons\/:id",
                "name": "Display the specified resource.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "906e5facac501f74eeb37866bcf0e5e5",
                "method": "PUT",
                "path": "\/api\/add-ons\/:id",
                "name": "Update the specified resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "price",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "period",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "limit",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "215315ea36e633dd785a6b1fabbeea7b",
                "method": "DELETE",
                "path": "\/api\/add-ons\/:id",
                "name": "Remove the specified resource from storage.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "INQUIRIES",
        "endpoints": [
            {
                "id": "d1f690aade57324efa93a430a0d9159c",
                "method": "POST",
                "path": "\/api\/inquiries\/:inquiry",
                "name": "POST api\/inquiries\/{inquiry}",
                "description": "",
                "params": [
                    {
                        "name": "customer_name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "phone",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "project_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "3590feaad83b85bd0fe79313c19be565",
                "method": "GET",
                "path": "\/api\/inquiries",
                "name": "GET api\/inquiries",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "a7d58ef44c058a1a4a64327418b5f3a9",
                "method": "POST",
                "path": "\/api\/inquiries",
                "name": "POST api\/inquiries",
                "description": "",
                "params": [
                    {
                        "name": "customer_name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "phone",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "project_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "06e48559bf3f357333dfb210f83e7ebf",
                "method": "GET",
                "path": "\/api\/inquiries\/:id",
                "name": "GET api\/inquiries\/{id}",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "a39f065a85ab9b3d6b79b07db6460f3a",
                "method": "PUT",
                "path": "\/api\/inquiries\/:id",
                "name": "PUT api\/inquiries\/{id}",
                "description": "",
                "params": [
                    {
                        "name": "customer_name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "phone",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "project_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "258106376e9cfc17a6c5f2e8abb36ea1",
                "method": "DELETE",
                "path": "\/api\/inquiries\/:id",
                "name": "DELETE api\/inquiries\/{id}",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "NOTIFICATIONS",
        "endpoints": [
            {
                "id": "abb9e24ef374619157c5332b02d97491",
                "method": "GET",
                "path": "\/api\/notifications",
                "name": "GET api\/notifications",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "a997bc713585248da8e22f12d2efda41",
                "method": "GET",
                "path": "\/api\/notifications\/unread",
                "name": "GET api\/notifications\/unread",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "a1e31a086851abb3e180028bc59e8be1",
                "method": "POST",
                "path": "\/api\/notifications\/mark-all-read",
                "name": "POST api\/notifications\/mark-all-read",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "366fa6e49adf7c432bdeaa12fdcf6047",
                "method": "POST",
                "path": "\/api\/notifications\/:id\/read",
                "name": "POST api\/notifications\/{id}\/read",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "e3d7ba23c412ac90b077ff65cf9a4484",
                "method": "POST",
                "path": "\/api\/notifications\/:id\/unread",
                "name": "POST api\/notifications\/{id}\/unread",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "TENANT-PROVISION",
        "endpoints": [
            {
                "id": "f3f8725249b7a7048467ca48fa8f5010",
                "method": "POST",
                "path": "\/api\/tenant-provision",
                "name": "POST api\/tenant-provision",
                "description": "",
                "params": [
                    {
                        "name": "business_name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "primary_contact_email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "phone_number",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "industry",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "address",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "product_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "plan_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "domain_type",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "domain",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "transaction_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "amount",
                        "type": "double",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "currency",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "payment_method",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "payment_status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "c56173943f7ace0ee7637aaac80d0999",
                "method": "POST",
                "path": "\/api\/tenant-provision\/:uuid\/verify-payment",
                "name": "POST api\/tenant-provision\/{uuid}\/verify-payment",
                "description": "",
                "params": [
                    {
                        "name": "razorpay_payment_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "razorpay_payment_link_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "razorpay_payment_link_status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "razorpay_signature",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "TENANTS",
        "endpoints": [
            {
                "id": "b27dc9e0071cd4c8c97e54aecf99fd1f",
                "method": "GET",
                "path": "\/api\/tenants",
                "name": "Display a listing of tenants.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "8629f702cdc07431d01bbe78bf2a44a3",
                "method": "GET",
                "path": "\/api\/tenants\/:uuid",
                "name": "Display the specified tenant by UUID.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "762e235e40c272bc77bffb3d1f859b2a",
                "method": "POST",
                "path": "\/api\/tenants\/:uuid",
                "name": "Update the specified tenant in storage.",
                "description": "",
                "params": [
                    {
                        "name": "business_name",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "primary_contact_email",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "phone_number",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "industry",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "address",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "domain_type",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "firebase_project_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "firebase_api_key",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "firebase_app_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "firebase_auth_domain",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "firebase_storage_bucket",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "firebase_messaging_sender_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "firebase_database_url",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "60ef80fff943510503bbe703391f4afa",
                "method": "DELETE",
                "path": "\/api\/tenants\/:uuid",
                "name": "Remove the specified tenant from storage (soft delete).",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "SUPPORT-TICKETS",
        "endpoints": [
            {
                "id": "7780d7349e3ee9c830815aac1ebadfa0",
                "method": "GET",
                "path": "\/api\/support-tickets",
                "name": "Display a listing of the resource.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "d71b9b862a1973cbdb5cb297a2180f01",
                "method": "GET",
                "path": "\/api\/support-tickets\/:id",
                "name": "Display the specified resource.",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "84682c860e878ef97b3bf00b60efbec2",
                "method": "POST",
                "path": "\/api\/support-tickets",
                "name": "Store a newly created resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "subject",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "priority",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "sla_deadline",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "ac615518e8d1c0d08e01a84c3061738d",
                "method": "POST",
                "path": "\/api\/support-tickets\/:id",
                "name": "Update the specified resource in storage.",
                "description": "",
                "params": [
                    {
                        "name": "subject",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "description",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "priority",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "sla_deadline",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "SUBSCRIPTIONS",
        "endpoints": [
            {
                "id": "2a3c0209dd99784144e76de91a9968ac",
                "method": "POST",
                "path": "\/api\/subscriptions\/:subscription",
                "name": "POST api\/subscriptions\/{subscription}",
                "description": "",
                "params": [
                    {
                        "name": "status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "start_date",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "end_date",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "358a297b0c807582571348d670b3d878",
                "method": "GET",
                "path": "\/api\/subscriptions",
                "name": "GET api\/subscriptions",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "4535021d552400970d7ced4e984ebde6",
                "method": "POST",
                "path": "\/api\/subscriptions",
                "name": "POST api\/subscriptions",
                "description": "",
                "params": [
                    {
                        "name": "tenant_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "plan_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "start_date",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "end_date",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "89fdb50e768e2c36886aac3d4c7295b7",
                "method": "GET",
                "path": "\/api\/subscriptions\/:id",
                "name": "GET api\/subscriptions\/{id}",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "0409811371486509c31409d5d95874a3",
                "method": "PUT",
                "path": "\/api\/subscriptions\/:id",
                "name": "PUT api\/subscriptions\/{id}",
                "description": "",
                "params": [
                    {
                        "name": "status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "start_date",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "end_date",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "19c41ee5c67d4730ffbd030fb27562e8",
                "method": "DELETE",
                "path": "\/api\/subscriptions\/:id",
                "name": "DELETE api\/subscriptions\/{id}",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    },
    {
        "category": "PAYMENTS",
        "endpoints": [
            {
                "id": "e648c08206bc9a3bef3c664e8a4561b8",
                "method": "POST",
                "path": "\/api\/payments\/:payment",
                "name": "POST api\/payments\/{payment}",
                "description": "",
                "params": [
                    {
                        "name": "transaction_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "amount",
                        "type": "double",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "currency",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "payment_method",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "1365bb40c3102c43f45c80a5b8f74cb3",
                "method": "GET",
                "path": "\/api\/payments",
                "name": "GET api\/payments",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "8dbcbf0d2ce195923fda8f7669f49e66",
                "method": "POST",
                "path": "\/api\/payments",
                "name": "POST api\/payments",
                "description": "",
                "params": [
                    {
                        "name": "tenant_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "transaction_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "amount",
                        "type": "double",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "currency",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "payment_method",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "77b4efbc8e88457f46b95d5072bd0864",
                "method": "GET",
                "path": "\/api\/payments\/:id",
                "name": "GET api\/payments\/{id}",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\"status\":\"error\",\"message\":\"Unauthenticated. Your token has expired or is missing.\"}"
            },
            {
                "id": "000b1579735301c2f2ee6adfe3f809f5",
                "method": "PUT",
                "path": "\/api\/payments\/:id",
                "name": "PUT api\/payments\/{id}",
                "description": "",
                "params": [
                    {
                        "name": "transaction_id",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "amount",
                        "type": "double",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "currency",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "payment_method",
                        "type": "string",
                        "required": true,
                        "description": ""
                    },
                    {
                        "name": "status",
                        "type": "string",
                        "required": true,
                        "description": ""
                    }
                ],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            },
            {
                "id": "57e09c8df66e260a01b71c9ce86c381e",
                "method": "DELETE",
                "path": "\/api\/payments\/:id",
                "name": "DELETE api\/payments\/{id}",
                "description": "",
                "params": [],
                "response_success": "{\n  \"status\": \"success\"\n}",
                "response_error": "{\n  \"message\": \"Unauthenticated.\"\n}"
            }
        ]
    }
];
