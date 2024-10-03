<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel File Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f9f9f9;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        form {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        input[type="text"],
        input[type="email"],
        input[type="date"],
        select {
            width: calc(100% - 12px);
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        input[type="file"] {
            margin-bottom: 15px;
        }

        button {
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }

        .accordion {
            margin-top: 20px;
        }

        .accordion-header {
            cursor: pointer;
            background-color: #007bff;
            color: white;
            padding: 10px;
            border: 1px solid #007bff;
            border-radius: 5px;
        }

        .accordion-header:hover {
            background-color: #0056b3;
        }

        .accordion-content {
            display: none;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-top: 5px;
            background-color: #f1f1f1;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
        }

        .col {
            flex: 1;
            padding: 10px;
            min-width: 200px;
        }
    </style>
    <script>
        function toggleAccordion(event) {
            const content = event.currentTarget.nextElementSibling;
            content.style.display = content.style.display === "block" ? "none" : "block";
        }

        async function handleUpload(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            try {
                const response = await fetch('upload.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    populateForm(data.data);
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error during upload:', error);
            }
        }

        function formatDateToISO(dateStr) {
            const [day, month, year] = dateStr.split('-');
            return `${year}-${month}-${day}`;
        }

        async function base64ToFile(base64, filename, mime) {
            const arr = base64.split(',');
            const bstr = atob(arr[0]);
            const n = bstr.length;
            const u8arr = new Uint8Array(n);

            for (let i = 0; i < n; i++) {
                u8arr[i] = bstr.charCodeAt(i);
            }

            return new File([u8arr], filename, {
                type: mime
            });
        }

        async function populateForm(dataArray) {
            const accordionContainer = document.querySelector('.accordion');
            accordionContainer.innerHTML = ''; // Clear existing content

            dataArray.forEach(async (data, index) => {
                const gender = data.gender ? data.gender.toLowerCase() : '';
                const relationship = data.relationship ? data.relationship.toLowerCase() : '';
                const formattedBirthDate = data.birth_date ? formatDateToISO(data.birth_date) : '';
                const passportFile = data.passport_file.base64 ?? '';
                const fileName = data.passport_file.name ?? '';

                const file = await base64ToFile(passportFile, fileName, data.passport_file.type); // Convert Base64 

                // Create a DataTransfer object to hold the File
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                let bindingFile = dataTransfer.files[0];
                // const fileInput = document.querySelector('#fileInput');
                // fileInput.files = bindingFile

                const personalInfoAccordion = `
            <div class="accordion-item">
                <div class="accordion-header" onclick="toggleAccordion(event)">
                    Personal Information ${index + 1}
                </div>
                <div class="accordion-content">
                    <div class="row">
                        <div class="col">
                            <label for="first_name_${index}">First Name:</label>
                            <input type="text" name="first_name_${index}" value="${data.first_name || ''}" required>
                        </div>
                        <div class="col">
                            <label for="second_name_${index}">Second Name:</label>
                            <input type="text" name="second_name_${index}" value="${data.second_name || ''}">
                        </div>
                        <div class="col">
                            <label for="last_name_${index}">Last Name:</label>
                            <input type="text" name="last_name_${index}" value="${data.last_name || ''}" required>
                        </div>
                        <div class="col">
                            <label for="email_${index}">Email:</label>
                            <input type="email" name="email_${index}" value="${data.email || ''}" required>
                        </div>
                        <div class="col">
                            <label for="telephone_${index}">Telephone:</label>
                            <input type="text" name="telephone_${index}" value="${data.telephone || ''}" required>
                        </div>
                        <div class="col">
                            <label for="passport_${index}">Passport:</label>
                            <input type="text" name="passport_${index}" value="${data.passport || ''}" required>
                        </div>
                        <div class="col">
                            <label for="nationality_${index}">Nationality:</label>
                            <input type="text" name="nationality_${index}" value="${data.nationality || ''}" required>
                        </div>
                        <div class="col">
                            <label for="occupation_${index}">Occupation:</label>
                            <input type="text" name="occupation_${index}" value="${data.occupation || ''}" required>
                        </div>
                        <div class="col">
                            <label for="birth_place_${index}">Birth Place:</label>
                            <input type="text" name="birth_place_${index}" value="${data.birth_place || ''}" required>
                        </div>
                        <div class="col">
                            <label for="birth_date_${index}">Birth Date:</label>
                            <input type="date" name="birth_date_${index}" value="${formattedBirthDate}" required>
                        </div>
                        <div class="col">
                            <label for="relationship_${index}">Relationship:</label>
                            <input type="text" name="relationship_${index}" value="${relationship}" required>
                        </div>
                        <div class="col">
                            <label for="gender_${index}">Gender:</label>
                            <select name="gender_${index}" required>
                                <option value="">Select Gender</option>
                                <option value="male" ${gender == 'male' ? 'selected' : ''}>Male</option>
                                <option value="female" ${gender == 'female' ? 'selected' : ''}>Female</option>
                                <option value="other" ${gender !== 'male' && gender !== 'female' ? 'selected' : ''}>Other</option>
                            </select>
                        </div>
                        <div class="col">
                            <label for="passport_file_${index}">Passport File:</label>
                            <input id="fileInput" type="file" value="${bindingFile}" name="passport_file_${index}" accept=".jpg, .png, .pdf">
                            <span>${data.passport_file_name || ''}</span> <!-- Show file name if available -->
                        </div>
                    </div>
                </div>
            </div>
        `;

                accordionContainer.innerHTML += personalInfoAccordion;
            });
        }
    </script>
</head>

<body>

    <h1>Upload Excel File with Passport Files</h1>

    <form action="" method="POST" enctype="multipart/form-data" onsubmit="handleUpload(event)">
        <label for="excel_file">Upload Excel File:</label>
        <input type="file" name="excel_file" accept=".xlsx, .xls" required>

        <button type="submit">Upload</button>
    </form>

    <div class="accordion">
        <!-- This content will be populated dynamically -->
    </div>

</body>

</html>