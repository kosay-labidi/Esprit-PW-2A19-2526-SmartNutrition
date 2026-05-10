import sys
import json
import cv2

def detect_face(image_path):
    try:
        img = cv2.imread(image_path)
        if img is None:
            return json.dumps({'face_detected': False, 'error': 'Cannot load image'})

        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')

        faces = face_cascade.detectMultiScale(gray, 1.3, 5)

        if len(faces) > 0:
            return json.dumps({
                'face_detected': True,
                'faces_count': len(faces),
                'image_path': image_path
            })
        else:
            return json.dumps({'face_detected': False, 'error': 'No face detected'})

    except Exception as e:
        return json.dumps({'face_detected': False, 'error': str(e)})

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print(json.dumps({'face_detected': False, 'error': 'Invalid arguments'}))
        sys.exit(1)

    result = detect_face(sys.argv[1])
    print(result)