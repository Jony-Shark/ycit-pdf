# ycit-pdf
Test task solution.

## Working
- Loop over folders and check every 1.png file to read QRcode to condition
- Save folders to an array
- Order directories by name
- Get data from JSON to generae a simple html page to PDF
- Check & merge files into a PDF
- return or save a PDF to pdfs folder

## Functioning
1. If we call the ```search.php``` file without parameters, it goes through all the elements of the session and tries to generate a PDF if a fax is received.
2. We can call the ```search.php``` file by entering a specific ```uuid```, (```search.php?people={uuid}```) then it will only return the pdf generated from the given person.

### TODO: Missing functions
- Create routing to a fancy url call
- Make the calling secure
