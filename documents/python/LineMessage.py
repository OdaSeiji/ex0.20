from linebot.v3.messaging import (
    Configuration,
    ApiClient,
    MessagingApi,
    PushMessageRequest,
    TextMessage,
)
import os

# ----------------- Set Authentication Info and Recipient -----------------
# 🚨 [CAUTION] Set your *newly reissued Channel Access Token* here.
#             In a production environment, reading from an environment variable is strongly recommended.
LINE_CHANNEL_ACCESS_TOKEN = "IPIgGpCfEqYFj8MeB5dVrckqrOmIrpPowqNrOSDbzzV3mMF/QVZ4hoefeO9VZ6fYZiF6d7a05a59Wdr3v4JD1Ne2b9xTYf1pzkkQB4zuCI+gwH8pTMf6yFf/nAvHbhYBVuDGFNQYINYjEb7PzwZwVQdB04t89/1O/w1cDnyilFU="

# Set the 'User ID' of the recipient.
# ⚠️ This ID must belong to a user who is already friends with the Bot.
TO_USER_ID = "U779b602d8167753d5662e9335c590c0c" 

# The content of the message to be sent
MESSAGE_TEXT = "Pythonスクリプトからのテストメッセージです！" # Note: The message content itself remains Japanese in this example
# --------------------------------------------------------

# SDK configuration
configuration = Configuration(
    access_token=LINE_CHANNEL_ACCESS_TOKEN
)

def send_line_push_message(to_id: str, text: str):
    """Function to send a push message using the LINE Messaging API"""
    print(f"Attempting to send message to {to_id}...")
    
    # Check if the token is set (this is the original placeholder check logic)
    if LINE_CHANNEL_ACCESS_TOKEN == "YOUR_NEWLY_ISSUED_ACCESS_TOKEN_HERE":
        print("❌ Error: Please set the Channel Access Token correctly.")
        return

    try:
        # Create API client and MessagingApi instance
        with ApiClient(configuration) as api_client:
            line_bot_api = MessagingApi(api_client)

            # Create the message object to send (Text Message in this case)
            messages = [
                TextMessage(text=text)
            ]

            # Create the push message request
            push_message_request = PushMessageRequest(
                to=to_id,
                messages=messages
            )

            # Execute the API call to send the message
            line_bot_api.push_message(push_message_request)
            
            print("✅ Success: Message sent. Please check your LINE app.")

    except Exception as e:
        # If an error occurs
        print(f"❌ Failed: An error occurred while sending the message.")
        print(f"Details: {e}")

# Execution
if __name__ == "__main__":
    send_line_push_message(TO_USER_ID, MESSAGE_TEXT)