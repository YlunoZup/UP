import os
import csv
import random
from flask import Flask, request, send_from_directory, jsonify
from werkzeug.utils import secure_filename

app = Flask(__name__)
UPLOAD_FOLDER = 'uploads'
OUTPUT_FOLDER = 'outputs'

os.makedirs(UPLOAD_FOLDER, exist_ok=True)
os.makedirs(OUTPUT_FOLDER, exist_ok=True)

@app.route('/')
def home():
    return send_from_directory('.', 'index.html')

@app.route('/split', methods=['POST'])
def split_csv():
    file = request.files['file']
    filename = secure_filename(file.filename)
    input_path = os.path.join(UPLOAD_FOLDER, filename)
    file.save(input_path)

    with open(input_path, newline='', encoding='utf-8') as csvfile:
        reader = list(csv.reader(csvfile))
        header = reader[0]
        rows = reader[1:]

    part_files = []
    index = 0
    part_num = 1

    while index < len(rows):
        max_rows = random.randint(800, 1000)
        chunk = rows[index:index+max_rows]
        part_name = f"{filename.rsplit('.', 1)[0]}_part{part_num}.csv"
        part_path = os.path.join(OUTPUT_FOLDER, part_name)
        with open(part_path, 'w', newline='', encoding='utf-8') as out_file:
            writer = csv.writer(out_file)
            writer.writerow(header)
            writer.writerows(chunk)
        part_files.append(part_name)
        index += max_rows
        part_num += 1

    return jsonify(part_files)

@app.route('/combine', methods=['POST'])
def combine_csv():
    uploaded_files = request.files.getlist('files')
    all_rows = []
    header = None

    for file in uploaded_files:
        filename = secure_filename(file.filename)
        filepath = os.path.join(UPLOAD_FOLDER, filename)
        file.save(filepath)

        with open(filepath, newline='', encoding='utf-8') as f:
            reader = list(csv.reader(f))
            if not header:
                header = reader[0]
            all_rows.extend(reader[1:])  

    
    seen = set()
    unique_rows = []
    for row in all_rows:
        key = tuple(row)
        if key not in seen:
            seen.add(key)
            unique_rows.append(row)

    
    yes_rows = []
    no_rows = []
    other_rows = []

    for row in unique_rows:
        if len(row) > 5:
            val = row[5].strip().lower()
            if val == 'yes':
                yes_rows.append(row)
            elif val == 'no':
                no_rows.append(row)
            else:
                other_rows.append(row)
        else:
            other_rows.append(row)

    
    combined_name = "combined_output.csv"
    combined_path = os.path.join(OUTPUT_FOLDER, combined_name)

    with open(combined_path, 'w', newline='', encoding='utf-8') as out_file:
        writer = csv.writer(out_file)
        writer.writerow(header)
        writer.writerows(yes_rows)
        writer.writerow([]) 
        writer.writerow([])
        writer.writerows(no_rows)
        writer.writerow([]) 
        writer.writerow([])
        writer.writerows(other_rows)

    return jsonify({"filename": combined_name})

@app.route('/download/<filename>')
def download(filename):
    return send_from_directory(OUTPUT_FOLDER, filename, as_attachment=True)

if __name__ == '__main__':
    app.run(debug=True)
