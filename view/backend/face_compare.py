import sys
import json
import cv2
import numpy as np

def compare_faces(img1_path, img2_path):
    try:
        # Charger les images
        img1 = cv2.imread(img1_path)
        img2 = cv2.imread(img2_path)
        
        if img1 is None or img2 is None:
            return json.dumps({'success': False, 'score': 0, 'error': 'Cannot load images'})
        
        # Convertir en niveaux de gris
        gray1 = cv2.cvtColor(img1, cv2.COLOR_BGR2GRAY)
        gray2 = cv2.cvtColor(img2, cv2.COLOR_BGR2GRAY)
        
        # Détecter les visages avec Haar Cascade
        face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
        
        faces1 = face_cascade.detectMultiScale(gray1, 1.3, 5)
        faces2 = face_cascade.detectMultiScale(gray2, 1.3, 5)
        
        if len(faces1) == 0 or len(faces2) == 0:
            return json.dumps({'success': False, 'score': 0, 'error': 'No face detected'})
        
        # Extraire la première face détectée
        (x1, y1, w1, h1) = faces1[0]
        (x2, y2, w2, h2) = faces2[0]
        
        face1 = gray1[y1:y1+h1, x1:x1+w1]
        face2 = gray2[y2:y2+h2, x2:x2+w2]
        
        # Redimensionner pour comparaison
        face1 = cv2.resize(face1, (100, 100))
        face2 = cv2.resize(face2, (100, 100))
        
        # Utiliser ORB pour la comparaison
        orb = cv2.ORB_create()
        
        kp1, des1 = orb.detectAndCompute(face1, None)
        kp2, des2 = orb.detectAndCompute(face2, None)
        
        if des1 is None or des2 is None:
            return json.dumps({'success': False, 'score': 0, 'error': 'No keypoints'})
        
        # Comparer les descripteurs
        bf = cv2.BFMatcher(cv2.NORM_HAMMING, crossCheck=True)
        matches = bf.match(des1, des2)
        
        # Trier par distance
        matches = sorted(matches, key=lambda x: x.distance)
        
        # Calculer le score de similarité
        if len(matches) > 0:
            avg_distance = np.mean([m.distance for m in matches[:50]])
            score = max(0, 1 - (avg_distance / 100))
        else:
            score = 0
        
        return json.dumps({
            'success': True,
            'score': float(score),
            'matches': len(matches)
        })
        
    except Exception as e:
        return json.dumps({'success': False, 'score': 0, 'error': str(e)})

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print(json.dumps({'success': False, 'error': 'Invalid arguments'}))
        sys.exit(1)
    
    result = compare_faces(sys.argv[1], sys.argv[2])
    print(result)