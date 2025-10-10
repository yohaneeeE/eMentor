# filename: decisiontree_api.py
"""
eMentor Career Path Prediction API (Enhanced with PaddleOCR)
-------------------------------------------------------------
- Supports image and PDF uploads (TORs or certificates)
- Extracts subjects and grades reliably using PaddleOCR
- Handles scanned PDFs with OCR fallback
- Predicts career paths using RandomForest
- Suggests suitable career options and highlights strengths/weaknesses
"""

import io
import re
import os
import tempfile
import pandas as pd
import numpy as np
from typing import List, Dict
from fastapi import FastAPI, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import LabelEncoder
from paddleocr import PaddleOCR
import fitz  # PyMuPDF for PDF text extraction

app = FastAPI(title="eMentor Career Predictor API")

# Allow frontend connections
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize PaddleOCR engine
ocr = PaddleOCR(use_angle_cls=True, lang='en')

# Load or fallback training data
CSV_PATH = "cs_students.csv"
if os.path.exists(CSV_PATH):
    df = pd.read_csv(CSV_PATH)
else:
    # fallback dummy data
    df = pd.DataFrame({
        'Math': [90, 75, 85, 60, 95],
        'Science': [80, 70, 90, 50, 88],
        'English': [85, 80, 75, 65, 92],
        'Programming': [95, 60, 85, 40, 98],
        'Career': ['Engineer', 'Teacher', 'Developer', 'Designer', 'Data Analyst']
    })

X = df.drop(columns=['Career'])
y = LabelEncoder().fit_transform(df['Career'])
model = RandomForestClassifier(n_estimators=100, random_state=42)
model.fit(X, y)
career_labels = list(LabelEncoder().fit(df['Career']).classes_)

# -------------------------------------------------
# Utility Functions
# -------------------------------------------------

def extract_text_from_image(image_bytes: bytes) -> str:
    """Extract text from image using PaddleOCR."""
    with tempfile.NamedTemporaryFile(delete=False, suffix=".jpg") as tmp:
        tmp.write(image_bytes)
        tmp.flush()
        result = ocr.ocr(tmp.name)
    # Flatten all lines from all pages
    text = " ".join([line[1][0] for page in result for line in page])
    return text

def extract_text_from_pdf(file_bytes: bytes) -> str:
    """Extract text from PDF, fallback to OCR for scanned pages."""
    text = ""
    with fitz.open(stream=file_bytes, filetype="pdf") as pdf:
        for page in pdf:
            page_text = page.get_text("text")
            if not page_text.strip():
                # Fallback OCR for scanned page
                pix = page.get_pixmap()
                img_bytes = pix.tobytes("png")
                page_text = extract_text_from_image(img_bytes)
            text += page_text + "\n"
    return text

def parse_subjects_and_grades(text: str) -> Dict[str, float]:
    """Parse OCR text and extract subjects with numeric grades."""
    subjects_map = {
        'math': 'Math',
        'algebra': 'Math',
        'science': 'Science',
        'chemistry': 'Science',
        'physics': 'Science',
        'english': 'English',
        'grammar': 'English',
        'programming': 'Programming',
        'coding': 'Programming',
        'computer': 'Programming',
        'ict': 'Programming'
    }

    # Clean text
    text = text.lower().replace(",", ".").replace("%", "")
    text = re.sub(r'[^a-z0-9\s\.\-]', ' ', text)
    lines = text.split("\n")
    subjects = {}

    for line in lines:
        for key, canonical in subjects_map.items():
            if key in line:
                match = re.search(r'(\b\d{1,3}\.?\d{0,2}\b)', line)
                if match:
                    grade = float(match.group(1))
                    # Convert to 0-100 scale if needed
                    if grade <= 5:
                        pct = (5 - grade) / 4 * 100
                    else:
                        pct = grade
                    subjects[canonical] = pct
    return subjects

def bucketize_subjects(subjects: Dict[str, float]) -> Dict[str, str]:
    """Convert numeric grades into qualitative buckets."""
    buckets = {}
    for subj, val in subjects.items():
        if val >= 90: buckets[subj] = "Excellent"
        elif val >= 80: buckets[subj] = "Good"
        elif val >= 70: buckets[subj] = "Average"
        else: buckets[subj] = "Needs Improvement"
    return buckets

def predict_career(subjects: Dict[str, float]) -> Dict:
    """Predicts career using RandomForest and returns structured result."""
    base = {col: np.mean(df[col]) for col in df.columns if col != 'Career'}
    base.update(subjects)
    X_pred = pd.DataFrame([base])[df.drop(columns=['Career']).columns]
    pred_label = model.predict(X_pred)[0]
    career = career_labels[pred_label]

    suggestions = {
        "Engineer": ["Software Engineer", "System Analyst", "QA Tester"],
        "Developer": ["Web Developer", "App Developer", "Full-Stack Engineer"],
        "Teacher": ["STEM Educator", "Instructor", "Tutor"],
        "Designer": ["Graphic Designer", "UI/UX Designer", "Animator"],
        "Data Analyst": ["Business Analyst", "Data Scientist", "Research Analyst"]
    }

    return {
        "careerPrediction": career,
        "careerOptions": suggestions.get(career, []),
        "finalBuckets": bucketize_subjects(subjects)
    }

# -------------------------------------------------
# API Routes
# -------------------------------------------------

@app.get("/")
def root():
    return {"message": "eMentor Career Predictor API is running."}

@app.post("/ocrPredict")
async def ocr_predict(file: UploadFile = File(...)):
    """Handles OCR + Prediction for images or PDFs."""
    try:
        file_bytes = await file.read()

        # Step 1: OCR Extraction
        if file.content_type == "application/pdf":
            text = extract_text_from_pdf(file_bytes)
        else:
            text = extract_text_from_image(file_bytes)

        # Debug: print OCR text
        print("=== OCR TEXT START ===")
        print(text)
        print("=== OCR TEXT END ===")

        if not text.strip():
            return {"error": "No readable text found in the uploaded file."}

        # Step 2: Parse Subjects and Grades
        subjects = parse_subjects_and_grades(text)
        if not subjects:
            return {"error": "No subjects or grades detected from the uploaded TOR."}

        # Step 3: Predict Career
        result = predict_career(subjects)

        # Step 4: Return structured JSON
        return {
            "message": "Analysis successful.",
            "subjects_structured": subjects,
            "careerPrediction": result["careerPrediction"],
            "careerOptions": result["careerOptions"],
            "finalBuckets": result["finalBuckets"]
        }

    except Exception as e:
        return {"error": str(e)}

# -------------------------------------------------
# Optional: Analyze Multiple Certificates
# -------------------------------------------------

@app.post("/analyzeCertificates")
async def analyze_certificates(files: List[UploadFile] = File(...)):
    """Accept multiple certificate uploads and extract keywords."""
    keywords = []
    for file in files:
        try:
            content = await file.read()
            if file.content_type == "application/pdf":
                text = extract_text_from_pdf(content)
            else:
                text = extract_text_from_image(content)
            for kw in ["python", "leadership", "java", "communication", "excel", "project"]:
                if re.search(kw, text.lower()):
                    keywords.append(kw)
        except Exception:
            continue

    return {"certificates": keywords, "count": len(keywords)}

# -------------------------------------------------
# Run: uvicorn decisiontree_api:app --reload
# -------------------------------------------------
