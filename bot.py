import logging
import random
import os
import io
import asyncio
import html
from typing import Dict, List, Any
from concurrent.futures import ThreadPoolExecutor

from telegram import (
    Update,
    InlineKeyboardButton,
    InlineKeyboardMarkup,
)
from telegram.ext import (
    ApplicationBuilder,
    CommandHandler,
    CallbackQueryHandler,
    ContextTypes,
    CallbackContext,
)
from telegram.constants import ParseMode
from gtts import gTTS

# Import questions from questions.py
from questions import ALL_QUESTIONS

# --- Configuration ---
# Replace with your actual bot token
TOKEN = "YOUR_BOT_TOKEN_HERE"

# --- Logging Setup ---
logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)
logger = logging.getLogger(__name__)

# --- Game Constants ---
QUESTIONS_PER_ROUND = 10
QUESTION_TIMEOUT = 15  # seconds

# --- Global Game State ---
games: Dict[int, Dict[str, Any]] = {}
thread_pool = ThreadPoolExecutor(max_workers=4)

# --- Helper Functions ---

def sync_get_audio_bytes(text: str) -> io.BytesIO:
    """Synchronous function to generate TTS."""
    tts = gTTS(text=text, lang='en')
    fp = io.BytesIO()
    tts.write_to_fp(fp)
    fp.seek(0)
    return fp

async def get_audio_bytes(text: str) -> io.BytesIO:
    """Asynchronously converts text to speech by running gTTS in a thread pool."""
    loop = asyncio.get_event_loop()
    return await loop.run_in_executor(thread_pool, sync_get_audio_bytes, text)

def get_leaderboard(game: Dict[str, Any]) -> str:
    """Generates a ranked leaderboard string with escaped HTML."""
    participants = game["participants"]
    sorted_players = sorted(
        participants.values(), key=lambda x: x["score"], reverse=True
    )

    leaderboard = "<b>🏆 Game Over! Final Leaderboard:</b>\n\n"
    for i, player in enumerate(sorted_players, 1):
        medal = "🥇" if i == 1 else "🥈" if i == 2 else "🥉" if i == 3 else "👤"
        escaped_name = html.escape(player['name'])
        leaderboard += f"{medal} {escaped_name} — {player['score']} pts\n"

    return leaderboard

# --- Command Handlers ---

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Simple welcome message."""
    await update.message.reply_html(
        "Welcome to the <b>English Quiz Bot</b>! 🎓\n\n"
        "Add me to a group and type /play to start a game."
    )

async def play(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Starts the joining phase of the game."""
    chat_id = update.effective_chat.id
    user_id = update.effective_user.id

    if chat_id in games:
        await update.message.reply_html("A game is already in progress or being set up in this chat.")
        return

    # Initialize game state
    games[chat_id] = {
        "status": "joining",
        "participants": {},
        "questions": random.sample(ALL_QUESTIONS, min(QUESTIONS_PER_ROUND, len(ALL_QUESTIONS))),
        "current_question_index": 0,
        "answered_users": set(),
        "admin_id": user_id,
        "join_message_id": None,
        "question_message_id": None,
        "transitioning": False  # New flag to prevent race conditions
    }

    keyboard = [
        [InlineKeyboardButton("Join Game ➕", callback_data="join")],
        [InlineKeyboardButton("Start Game ▶️", callback_data="start_game")]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)

    msg = await update.message.reply_html(
        f"<b>🎮 English Quiz Game</b>\n\n"
        f"Host: {update.effective_user.mention_html()}\n"
        f"Click the button below to join! The host can start the game when ready.",
        reply_markup=reply_markup
    )
    games[chat_id]["join_message_id"] = msg.message_id

# --- Callback Handlers ---

async def handle_callbacks(update: Update, context: ContextTypes.DEFAULT_TYPE):
    query = update.callback_query
    chat_id = update.effective_chat.id
    user_id = update.effective_user.id
    user_name = update.effective_user.first_name

    if chat_id not in games:
        await query.answer("No active game found.")
        return

    game = games[chat_id]
    data = query.data

    # JOIN LOGIC
    if data == "join":
        if game["status"] != "joining":
            await query.answer("Joining phase is over.", show_alert=True)
            return

        if user_id in game["participants"]:
            await query.answer("You've already joined!")
        else:
            game["participants"][user_id] = {"name": user_name, "score": 0}
            await query.answer("You joined the game!")
            count = len(game["participants"])
            keyboard = [
                [InlineKeyboardButton(f"Join Game ({count}) ➕", callback_data="join")],
                [InlineKeyboardButton("Start Game ▶️", callback_data="start_game")]
            ]
            reply_markup = InlineKeyboardMarkup(keyboard)
            try:
                await query.edit_message_reply_markup(reply_markup=reply_markup)
            except Exception:
                pass

    # START GAME LOGIC
    elif data == "start_game":
        if user_id != game["admin_id"]:
            await query.answer("Only the host can start the game!", show_alert=True)
            return

        if not game["participants"]:
            await query.answer("No one has joined yet!", show_alert=True)
            return

        game["status"] = "playing"
        await query.message.delete()
        await send_next_question(context, chat_id)

    # ANSWER LOGIC
    elif data.startswith("ans_"):
        if game["status"] != "playing":
            return

        # Format: ans_{question_index}_{option_index}
        parts = data.split("_")
        callback_q_index = int(parts[1])
        selected_index = int(parts[2])

        # Validate this is for the CURRENT question
        if callback_q_index != game["current_question_index"] or game["transitioning"]:
            await query.answer("This question is no longer active.", show_alert=True)
            return

        if user_id not in game["participants"]:
            await query.answer("You are not a participant in this game!", show_alert=True)
            return

        if user_id in game["answered_users"]:
            await query.answer("You already answered this question!", show_alert=True)
            return

        game["answered_users"].add(user_id)

        current_q = game["questions"][game["current_question_index"]]
        if selected_index == current_q["answer"]:
            game["participants"][user_id]["score"] += 1
            await query.answer("Correct! ✅")
        else:
            await query.answer("Wrong! ❌")

        # Check if everyone has answered
        if len(game["answered_users"]) >= len(game["participants"]):
            await proceed_to_next(context, chat_id, game["current_question_index"])

# --- Quiz Logic ---

async def send_next_question(context: ContextTypes.DEFAULT_TYPE, chat_id: int):
    if chat_id not in games:
        return

    game = games[chat_id]
    game["transitioning"] = False
    index = game["current_question_index"]

    if index >= len(game["questions"]):
        leaderboard = get_leaderboard(game)
        await context.bot.send_message(chat_id, leaderboard, parse_mode=ParseMode.HTML)
        del games[chat_id]
        return

    question = game["questions"][index]
    game["answered_users"] = set()

    # Prepare buttons with question index for validation
    keyboard = []
    row = []
    for i, opt in enumerate(question["options"]):
        row.append(InlineKeyboardButton(opt, callback_data=f"ans_{index}_{i}"))
        if len(row) == 2:
            keyboard.append(row)
            row = []
    if row:
        keyboard.append(row)
    reply_markup = InlineKeyboardMarkup(keyboard)

    header = f"<b>Question {index + 1}/{len(game['questions'])}</b>\n\n"
    text = header + question["text"]

    try:
        if question["type"] == "audio":
            audio_file = await get_audio_bytes(question["audio_text"])
            msg = await context.bot.send_audio(
                chat_id,
                audio=audio_file,
                caption=text,
                reply_markup=reply_markup,
                parse_mode=ParseMode.HTML
            )
        elif question["type"] == "image":
            msg = await context.bot.send_photo(
                chat_id,
                photo=question["image_path"],
                caption=text,
                reply_markup=reply_markup,
                parse_mode=ParseMode.HTML
            )
        else:
            msg = await context.bot.send_message(
                chat_id,
                text,
                reply_markup=reply_markup,
                parse_mode=ParseMode.HTML
            )

        game["question_message_id"] = msg.message_id

        # Schedule the timeout
        context.job_queue.run_once(
            question_timeout_callback,
            QUESTION_TIMEOUT,
            data={"chat_id": chat_id, "index": index},
            name=f"timeout_{chat_id}_{index}"
        )

    except Exception as e:
        logger.error(f"Error sending question: {e}")
        await context.bot.send_message(chat_id, f"An error occurred: {html.escape(str(e))}. Moving to next...")
        game["current_question_index"] += 1
        await send_next_question(context, chat_id)

async def question_timeout_callback(context: CallbackContext):
    data = context.job.data
    await proceed_to_next(context, data["chat_id"], data["index"])

async def proceed_to_next(context: CallbackContext, chat_id: int, index: int):
    if chat_id not in games:
        return

    game = games[chat_id]
    # Guard against multiple calls for the same question
    if game["current_question_index"] != index or game["transitioning"]:
        return

    game["transitioning"] = True

    # Cancel any existing timeout jobs for this question
    jobs = context.job_queue.get_jobs_by_name(f"timeout_{chat_id}_{index}")
    for job in jobs:
        job.schedule_removal()

    current_q = game["questions"][index]
    correct_text = current_q["options"][current_q["answer"]]

    await context.bot.send_message(
        chat_id,
        f"⏰ Time's up (or everyone answered)!\nThe correct answer was: <b>{html.escape(correct_text)}</b>",
        parse_mode=ParseMode.HTML
    )

    await asyncio.sleep(2)
    game["current_question_index"] += 1
    await send_next_question(context, chat_id)

# --- Error Handling ---

async def error_handler(update: object, context: ContextTypes.DEFAULT_TYPE) -> None:
    logger.error("Exception while handling an update:", exc_info=context.error)

# --- Main ---

def main():
    # To use a proxy (e.g. SOCKS5) in v20+:
    # from telegram.ext import HTTPXRequest
    # request = HTTPXRequest(proxy_url="socks5://user:pass@host:port")
    # application = ApplicationBuilder().token(TOKEN).request(request).build()

    application = ApplicationBuilder().token(TOKEN).build()

    application.add_handler(CommandHandler("start", start))
    application.add_handler(CommandHandler("play", play))
    application.add_handler(CallbackQueryHandler(handle_callbacks))
    application.add_error_handler(error_handler)

    print("Bot is running...")
    application.run_polling()

if __name__ == "__main__":
    main()
