<?php

// Read the CSV file and convert it to an associative array
function parseCSV($filename) {
    $rows = array();
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $headers = fgetcsv($handle);  // Read headers

        // Ensure the headers are not empty
        if ($headers === false || count($headers) == 0) {
            die("Error: Invalid CSV file structure.");
        }

        while (($data = fgetcsv($handle)) !== FALSE) {
            // Skip rows that don't match the header length
            if (count($data) == count($headers)) {
                $rows[] = array_combine($headers, $data);  // Combine headers with row data
            }
        }
        fclose($handle);
    } else {
        die("Error: Unable to open the CSV file.");
    }
    return $rows;
}

// Read CSV data
$commands = parseCSV("linux_commands.csv");

$commandOptions = [];

// Organize data by command
foreach ($commands as $command) {
    $commandOptions[$command['Command']][] = $command;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linux Command Generator</title>
    <style>
        .form-container {
            margin: 20px;
        }
        .option-group {
            margin-bottom: 10px;
        }
        .option-group label {
            margin-right: 10px;
        }
        .help-text {
            font-size: 0.9em;
            color: gray;
        }
        #options-container {
            margin-top: 15px;
        }
        .option-group {
            display: flex;
            align-items: center;
            gap: 10px; /* Space between items */
            margin-bottom: 10px; /* Space between option groups */
        }

        .option-group label {
            font-weight: bold;
            margin-right: 10px;
        }

        .option-group .help-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .option-group input[type="checkbox"] {
            margin-right: 10px;
        }

        .option-group input[type="text"] {
            width: 50%;
            padding: 5px;
            margin-top: 5px;
        }
         
        #paths-container .option-group {
            display: block; /* This forces paths onto their own line */
            margin-bottom: 15px;
        }

        #paths-container label {
            margin-top: 10px;
            display: block; /* Ensure label is on its own line */
            margin-bottom: 5px;
        }
        #paths-container input {
            width: 50%;
        }
        #paths-container .option-group input[type="text"] {
            width: 100%;
            padding: 5px;
        }
        textarea {
            width: 50%;
            height: 50%;
        }
    </style>
</head>
<body>

<h1>Linux Command Generator</h1>

<form id="command-form" method="POST">
    <div>
        <label for="command-search">Search Commands:</label>
        <input type="text" id="command-search" placeholder="Type to search commands...">
    </div>
    <div  style="margin-top: 15px;">
        <label for="command_field">Select Command:</label>
        <select id="command_field" name="command_field" required>
            <option value="">Select a Command</option>
            <?php foreach ($commandOptions as $command => $options): ?>
                <?php $description = $options[0]['Description'] ?? ''; // Get the description for each command ?>
                <option value="<?= $command ?>"><?= $command ?> - <?= htmlspecialchars($description) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="options-container"></div>
    <div id="paths-container"></div>

    <button type="submit" style="margin-top: 46px;">Generate Command</button>
</form>

<h2>Generated Command</h2>
<div id="generated-commandxxx"></div>
<textarea readonly id="generated-command"></textarea>

<script>
    // Embedded commandOptions data for JavaScript usage
    const commandsData = <?= json_encode($commandOptions) ?>;

    // Populate command dropdown based on search input
    document.getElementById('command-search').addEventListener('input', function() {
    let searchQuery = this.value.toLowerCase();
    let commandDropdown = document.getElementById('command_field');
    
    Array.from(commandDropdown.options).forEach(option => {
        let optionText = option.textContent.toLowerCase();  // Include both command and description
        option.style.display = optionText.includes(searchQuery) || option.value === '' ? 'block' : 'none';
    });
    });


    // Handle command selection and generate the form dynamically
    document.getElementById('command_field').addEventListener('change', function() {
        let command = this.value;
        let optionsContainer = document.getElementById('options-container');
        let pathsContainer = document.getElementById('paths-container');
        let generatedCommandContainer = document.getElementById('generated-command');
        
        // Clear previous data
        optionsContainer.innerHTML = '';
        pathsContainer.innerHTML = '';
        generatedCommandContainer.innerHTML = '';  // Clear generated command
        
        if (command !== '') {
            fetchOptions(command);
        }
    });

    function fetchOptions(command) {
    let optionsContainer = document.getElementById('options-container');
    let pathsContainer = document.getElementById('paths-container');
    
    // Clear the existing content before rendering new options
    optionsContainer.innerHTML = '';
    pathsContainer.innerHTML = '';

    let commandsData = <?= json_encode($commandOptions) ?>;
    let selectedCommand = commandsData[command];
    let count = 0;
    selectedCommand.forEach(function(option) {
        count += 1;
        // Skip blank options (e.g., if Option is blank)
        if (option['Option']) {
            let optionGroup = document.createElement('div');
            optionGroup.classList.add('option-group');
            
            // Option label
            let label = document.createElement('label');
            label.setAttribute('for', option['Option']); // For better accessibility
            label.textContent = option['Option'];
            optionGroup.appendChild(label);
 

            // Option type handling (checkbox, text)
            if (option['Type'] === 'checkbox') {
                let input = document.createElement('input');
                input.type = 'checkbox';
                input.name = option['Option']; // Use the option name for the checkbox input
                input.id = option['Option'];
                optionGroup.appendChild(input);
            } else if (option['Type'] === 'text' || option['Type'] === 'input') {
                let input = document.createElement('input');
                input.type = 'text';
                input.name = option['Option']; // Use the option name for the text input
                input.id = option['Option'];
                //input.required = option['Required'] || option['PathRequired'];
                optionGroup.appendChild(input);
            }

            // Help description (if present)
            if (option['Help Description']) {
                let helpText = document.createElement('div');
                helpText.classList.add('help-text');
                helpText.textContent = option['Help Description'];
                optionGroup.appendChild(helpText);
            }

            // Append the option group to the options container
            optionsContainer.appendChild(optionGroup);
        }else if (option['Type'] === 'path' || option['Type'] === 'text' || option['Type'] === 'input') {
            // Handle path inputs separately
            // Create a label for the path
            let pathLabel = document.createElement('label');
            pathLabel.textContent = option['Help Description'];  // Custom label for path input
            pathsContainer.appendChild(pathLabel);
            
            let input = document.createElement('input');
            input.type = 'text';
            input.name = option['FieldName'] || option['Placeholder'] || option['Command']+count; // Ensure path input has a unique name
            input.placeholder = `Enter ${option['Placeholder']}`; // Path placeholder
            input.required = option['Required'] || option['PathRequired'];
            pathsContainer.appendChild(input);
        }
    });
}

   
// Function to add quotes to paths that contain spaces or special characters
function addQuotesIfNeeded(value) {
    // Check if the value contains spaces or special characters
    const noQuoteCommands = ["su", "sudo", "cd"];
    if ((/\s/.test(value) || /[^a-zA-Z0-9\/\.\-_]/.test(value)) && !noQuoteCommands.some(cmd => value.startsWith(cmd))) {
        return `"${value}"`;  // Wrap in quotes if necessary
    }
    return value;  // Return without quotes if no spaces or special characters
}
// Handle form submission to generate the command
document.getElementById('command-form').addEventListener('submit', function(event) {
    event.preventDefault();

    let command = document.getElementById('command_field').value;
    let formData = new FormData(this);
    let generatedCommand = command;

    // Get command options from PHP
    let commandsData = <?= json_encode($commandOptions) ?>;
    let selectedCommand = commandsData[command];

    // Iterate over formData and check if the input is related to paths or options
    formData.forEach(function(value, key) {
        if (key !== 'command_field') {
            // Check if the key corresponds to a path (based on your CSV configuration)
            // let isPath = selectedCommand.some(option => option.Path === key || option.PathType === key);
            let isOption_with_input = selectedCommand.some(option => option.Option === key.trim());
            // Handle checkboxes
            if (value === 'on') {
                generatedCommand += ` ${key}`;  // Add option key if checkbox is checked
            }
            // Handle text-based or path fields dynamically
            else if (value.trim() !== '') {
                if (isOption_with_input){
                 generatedCommand += ` ${key}` + ` ${addQuotesIfNeeded(value.trim())}`; // Add quotes if necessary
                }else{
                 generatedCommand += ` ${addQuotesIfNeeded(value.trim())}`; // Add quotes if necessary
                }
            }
            
        }
    });

    // Display the generated command
    document.getElementById('generated-command').textContent = generatedCommand;
});




</script>

</body>
</html>
