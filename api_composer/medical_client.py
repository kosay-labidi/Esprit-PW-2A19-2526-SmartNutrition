# api_composer/medical_client.py
import httpx
import os
from dotenv import load_dotenv
from email_sender import send_email_smtp

async def send_notification(channel: str, to: str, subject: str = "", body: str = ""):
    if channel == "email":
        success = await send_email_smtp(to, subject, body)
        if not success:
            print(f"📧 [FALLBACK] Would send email to {to}\nSubject: {subject}\nBody: {body[:200]}")
    elif channel == "sms":
        # Implement SMS gateway if needed
        print(f"📱 [SMS] To: {to} | Body: {body[:160]}")

load_dotenv()

PHP_API_URL = os.getenv("PHP_API_URL")
API_KEY = os.getenv("API_KEY")

async def fetch_medical_dossier(user_id: int) -> dict:
    """
    Call the PHP MedicalApiController to get patient data.
    """
    async with httpx.AsyncClient() as client:
        resp = await client.get(
            PHP_API_URL,
            params={"user_id": user_id},
            headers={"X-API-Key": API_KEY},
            timeout=10.0
        )
        resp.raise_for_status()
        return resp.json()

async def send_notification(channel: str, to: str, subject: str = "", body: str = ""):
    """
    Tell PHP to send an email or SMS notification.
    """
    async with httpx.AsyncClient() as client:
        await client.post(
            os.getenv("PHP_NOTIFICATION_URL"),
            json={"channel": channel, "to": to, "subject": subject, "body": body},
            headers={"X-API-Key": API_KEY},
            timeout=5.0
        )