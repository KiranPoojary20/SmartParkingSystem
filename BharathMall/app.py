from flask import Flask, render_template, Response
import cv2
import torch
import easyocr
import mysql.connector
from datetime import datetime
import os
from ultralytics import YOLO

app = Flask(__name__)

# Initialize the camera
camera = cv2.VideoCapture(0)  # Change to 1 if you have an external webcam

# Load YOLOv8 model for plate detection
model = YOLO("best.pt")  # Replace with your trained model

# Initialize EasyOCR reader
reader = easyocr.Reader(['en'])

# Connect to MySQL database
db_connection = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="mall"
)
cursor = db_connection.cursor()

# Ensure the images directory exists
if not os.path.exists("images"):
    os.makedirs("images")

def generate_frames():
    while True:
        success, frame = camera.read()
        if not success:
            break
        else:
            # Run YOLOv8 detection on the frame
            results = model(frame)
            for r in results:
                for box in r.boxes:
                    x1, y1, x2, y2 = map(int, box.xyxy[0])
                    plate_crop = frame[y1:y2, x1:x2]  # Crop the license plate area
                    
                    # Run OCR on the cropped plate area
                    text = reader.readtext(plate_crop, detail=0)
                    detected_text = " ".join(text)
                    
                    # Draw bounding box and detected text on the frame
                    cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 0), 2)
                    cv2.putText(frame, detected_text, (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)
                    
                    # Get current date and time
                    current_date = datetime.now().date()
                    current_time = datetime.now().time()
                    
                    # Generate filename for the image (you can customize this)
                    image_filename = f"{current_date}_{current_time}.jpg"
                    
                    # Save the cropped image of the license plate
                    cv2.imwrite(f'images/{image_filename}', plate_crop)
                    
                    # Insert the recognized data into MySQL database
                    # Assuming floor_id and slot_id are predefined or determined by your system
                    cursor.execute("""
                        INSERT INTO live (floor_id, slot_id, v_no, v_image, ex_time)
                        VALUES (%s, %s, %s, %s, %s)
                    """, (1, 1, detected_text, image_filename, datetime.now()))
                    
                    # Commit the transaction to the database
                    db_connection.commit()
            
            # Encode frame as JPEG
            _, buffer = cv2.imencode('.jpg', frame)
            frame = buffer.tobytes()
            
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + frame + b'\r\n')

@app.route('/')
def index():
    return render_template('live.html')

@app.route('/video_feed')
def video_feed():
    return Response(generate_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, threaded=True, debug=False)