# retrain_model.py
import pandas as pd
import numpy as np
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import StandardScaler
import joblib
import os

def retrain():
    df = pd.read_csv("adherence_feedback.csv")
    # Only use rows where success is known (e.g., after 4 weeks you ask "Did you reach your goal?")
    # For now, we'll use a proxy: assume success = 1 if adherence_score >= 4
    df['success'] = (df['adherence_score'] >= 4).astype(int)
    X = df[['age', 'bmi', 'adherence_score']].values
    y = df['success'].values
    
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    model = LogisticRegression()
    model.fit(X_scaled, y)
    
    joblib.dump(model, "outcome_model.pkl")
    joblib.dump(scaler, "scaler.pkl")
    print("Model retrained and saved.")

if __name__ == "__main__":
    retrain()