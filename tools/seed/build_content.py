# Generates content.json (source of truth for draft copy: EN/NL/UK).
# Run from any directory: python3 tools/seed/build_content.py
import json
from pathlib import Path

D = "Draft copy for client review"
SERVICE_IMAGES = {
  "reiki": "session",
  "aromatouch": "session",
  "breath": "studio",
}


def P(text): return {"t": "p", "x": text}
def H2(text): return {"t": "h2", "x": text}
def H3(text): return {"t": "h3", "x": text}
def Q(text): return {"t": "quote", "x": text}
def N(text): return {"t": "note", "x": text}
def BTN(text, target, external=False): return {"t": "btn", "x": text, "to": target, "ext": external}
def HERO(heading, paragraph, button, target): return {"t": "hero", "h": heading, "x": paragraph, "b": button, "to": target}
def IMG(key, alt): return {"t": "image", "key": key, "alt": alt}


def COLS(*columns):
  return {
    "t": "cols",
    "items": [
      {
        "h": heading,
        "x": description,
        "to": page_key,
        "more": link_label,
        "image": SERVICE_IMAGES.get(page_key, ""),
      }
      for heading, description, page_key, link_label in columns
    ],
  }


def SEC(section_id, tone, *blocks): return {"t": "section", "id": section_id, "tone": tone, "blocks": list(blocks)}
def INC(page_name, section_id, tone): return {"t": "include", "page": page_name, "id": section_id, "tone": tone}
def FAQ(*items): return {"t": "faq", "items": [{"q": question, "a": answer} for question, answer in items]}

DISC={"en":"HATA Studio offers complementary practices for relaxation. They are not medical care and do not replace it.",
      "nl":"HATA Studio biedt aanvullende praktijken voor ontspanning. Het is geen medische zorg en vervangt die ook niet.",
      "uk":"HATA Studio пропонує комплементарні практики для розслаблення. Це не медична допомога і не замінює її."}
T_PRICE={"en":"You will see the current duration and price for each session in Aimy before confirming.","nl":"De actuele duur en prijs van iedere sessie zie je in Aimy voordat je de afspraak bevestigt.","uk":"Актуальні тривалість і вартість кожної сесії вказані в Aimy до підтвердження запису."}

pages={}
def page(key,slug,title,menu,**blocks):
    pages[key]={"slug":slug,"title":title,"menu":menu,"blocks":blocks}

# ---------- HOME ----------
home_b={
"en":[HERO("Come back to yourself","A gentle space for women to slow down and reconnect with your heart.","Book a session","book"),
  SEC("intro","linen",H2("You are already enough"),P("Life asks us to be faster, stronger, more productive. Here you can slow down, feel your body and take a breath. No expectations, no comparison, nothing to prove.")),
  SEC("practices","",H2("Gentle practices"),COLS(("Reiki","Deep relaxation that helps you move from thoughts to sensations.","reiki","Read more"),("AromaTouch","Slow, caring touch with essential oils on the back and feet.","aromatouch","Read more"),("Breath and mindfulness","Simple practices that help the calm stay with you.","breath","Read more"))),
  SEC("quote","linen",Q("Sometimes the body doesn't need answers. It needs to feel safe."),BTN("Book a session","book"))],
"nl":[HERO("Kom terug bij jezelf","Een zachte plek voor vrouwen om te vertragen en weer contact te maken met je hart.","Maak een afspraak","book"),
  SEC("intro","linen",H2("Je bent al genoeg"),P("Het leven vraagt ons om sneller, sterker en productiever te zijn. Hier mag je vertragen, je lichaam voelen en diep ademhalen. Zonder verwachtingen, zonder vergelijking, zonder iets te hoeven bewijzen.")),
  SEC("practices","",H2("Zachte praktijken"),COLS(("Reiki","Diepe ontspanning die je helpt van gedachten naar gevoel te gaan.","reiki","Lees meer"),("AromaTouch","Langzame, zorgzame aanraking met etherische oliën op rug en voeten.","aromatouch","Lees meer"),("Ademwerk en mindfulness","Eenvoudige oefeningen waardoor de rust langer blijft.","breath","Lees meer"))),
  SEC("quote","linen",Q("Soms heeft het lichaam geen antwoorden nodig. Het heeft veiligheid nodig."),BTN("Maak een afspraak","book"))],
"uk":[HERO("Повернись до себе","М’який простір для жінок, щоб сповільнитися і відновити зв’язок зі своїм серцем.","Записатися на сесію","book"),
  SEC("intro","linen",H2("Ти вже достатня"),P("Життя вимагає бути швидшою, сильнішою, продуктивнішою. Тут можна сповільнитися, відчути тіло і глибоко вдихнути. Без очікувань, без порівнянь, без потреби щось доводити.")),
  SEC("practices","",H2("М’які практики"),COLS(("Рейкі","Глибоке розслаблення, яке допомагає перейти від думок до відчуттів.","reiki","Докладніше"),("AromaTouch","Повільний дбайливий дотик з ефірними оліями на спині та стопах.","aromatouch","Докладніше"),("Дихання і усвідомленість","Прості практики, що допомагають спокою залишитися з тобою.","breath","Докладніше"))),
  SEC("quote","linen",Q("Іноді тілу не потрібні відповіді. Йому потрібно відчути безпеку."),BTN("Записатися на сесію","book"))],
}
pages["home"]={"slug":{"nl":"home","en":"home-en","uk":"home-uk"},"title":{"nl":"HATA Studio","en":"HATA Studio","uk":"HATA Studio"},"menu":{},"blocks":home_b}

# ---------- ABOUT ----------
page("about",{"nl":"over-hata","en":"about","uk":"pro-nas"},{"nl":"Over HATA","en":"About HATA","uk":"Про HATA"},{"nl":"Over HATA","en":"About","uk":"Про HATA"},
en=[P("I don't help women become someone else. I create a space where they can remember who they already are."),
 P("HATA is a quiet place to slow down. You don't need to be perfect or prove anything here. Sometimes you don't need advice or answers. You need a safe space, some silence and time to hear yourself again."),
 P("I work with Reiki, essential oils, AromaTouch and breathing practices. For me these are not magic and not promises of miracles. They are gentle tools that help the nervous system settle and the body let go of tension."),
 P("I know what it is like to build a life again in a new country and to live between two worlds. HATA is made with that in mind, and it is open to every woman who feels the need to stop, breathe out and find her footing."),
 H3("Why HATA?"),
 P("Hata means home in Ukrainian: a place of warmth and safety where you can be yourself. Houses with thatched or reed roofs belong to the landscape of both Ukraine and the Netherlands. To me they are a symbol of two countries that both live in my heart. The name also echoes Anahata, the heart chakra."),
 Q("Each woman is unique. She doesn't need to become someone else to be worthy of love.")],
nl=[P("Ik help vrouwen niet om iemand anders te worden. Ik maak een ruimte waar zij zich kunnen herinneren wie ze al zijn."),
 P("HATA is een rustige plek om te vertragen. Je hoeft hier niet perfect te zijn en niets te bewijzen. Soms heb je geen advies of antwoorden nodig. Je hebt een veilige ruimte nodig, stilte en tijd om jezelf weer te horen."),
 P("Ik werk met Reiki, etherische oliën, AromaTouch en ademwerk. Voor mij is dit geen magie en geen belofte van wonderen. Het zijn zachte hulpmiddelen die het zenuwstelsel helpen tot rust te komen en het lichaam spanning los te laten."),
 P("Ik weet hoe het is om in een nieuw land opnieuw een leven op te bouwen en tussen twee werelden te leven. HATA is met die gedachte gemaakt, en is er voor iedere vrouw die behoefte heeft om stil te staan, uit te ademen en weer houvast te voelen."),
 H3("Waarom HATA?"),
 P("Hata betekent thuis in het Oekraïens: een plek van warmte en veiligheid waar je jezelf mag zijn. Huizen met rieten daken horen bij het landschap van zowel Oekraïne als Nederland. Voor mij zijn ze een symbool van twee landen die allebei in mijn hart wonen. De naam doet ook denken aan Anahata, de hartchakra."),
 Q("Elke vrouw is uniek. Ze hoeft niemand anders te worden om liefde waard te zijn.")],
uk=[P("Я не допомагаю жінкам стати кимось іншим. Я створюю простір, де вони можуть згадати, ким були завжди."),
 P("HATA — це тихе місце, де можна сповільнитися. Тут не потрібно бути ідеальною чи щось доводити. Іноді не потрібні ні поради, ні готові відповіді. Потрібні безпечний простір, тиша і час, щоб знову почути себе."),
 P("У своїй роботі я використовую рейкі, ефірні олії, AromaTouch та дихальні практики. Для мене це не магія і не обіцянка див. Це м’які інструменти, які допомагають заспокоїти нервову систему і відпустити напругу в тілі."),
 P("Я знаю, як це — будувати життя заново в новій країні й жити між двома світами. HATA створена з цим розумінням і відкрита для кожної жінки, яка відчуває потребу зупинитися, видихнути й знову відчути внутрішню опору."),
 H3("Чому HATA?"),
 P("«Хата» — це дім: місце тепла й безпеки, де можна бути собою. Будинки з солом’яними чи очеретяними дахами — частина пейзажу і в Україні, і в Нідерландах. Для мене це символ двох країн, які однаково живуть у моєму серці. Назва також перегукується з Анахатою — серцевою чакрою."),
 Q("Кожна жінка унікальна. Їй не потрібно ставати кимось іншим, щоб бути гідною любові.")])

# ---------- SERVICES ----------
page("services",{"nl":"diensten","en":"services","uk":"posluhy"},{"nl":"Diensten","en":"Services","uk":"Послуги"},{"nl":"Diensten","en":"Services","uk":"Послуги"},
en=[P("Every session is different. We begin with a short conversation to set an intention and choose the tools together. We finish gently, with a cup of tea."),
 COLS(("Reiki","Deep relaxation that helps you move from thoughts to sensations.","reiki","Read more"),("AromaTouch","Slow, caring touch with essential oils on the back and feet.","aromatouch","Read more"),("Breath and mindfulness","Simple practices that help the calm stay with you.","breath","Read more")),
 P(T_PRICE["en"]),N(DISC["en"])],
nl=[P("Elke sessie is anders. We beginnen met een kort gesprek om een intentie te bepalen en samen de hulpmiddelen te kiezen. We sluiten rustig af, met een kop thee."),
 COLS(("Reiki","Diepe ontspanning die je helpt van gedachten naar gevoel te gaan.","reiki","Lees meer"),("AromaTouch","Langzame, zorgzame aanraking met etherische oliën op rug en voeten.","aromatouch","Lees meer"),("Ademwerk en mindfulness","Eenvoudige oefeningen waardoor de rust langer blijft.","breath","Lees meer")),
 P(T_PRICE["nl"]),N(DISC["nl"])],
uk=[P("Кожна сесія унікальна. Ми починаємо з короткої розмови, щоб сформулювати запит і разом обрати інструменти. Завершуємо м’яко, за чашкою чаю."),
 COLS(("Рейкі","Глибоке розслаблення, яке допомагає перейти від думок до відчуттів.","reiki","Докладніше"),("AromaTouch","Повільний дбайливий дотик з ефірними оліями на спині та стопах.","aromatouch","Докладніше"),("Дихання і усвідомленість","Прості практики, що допомагають спокою залишитися з тобою.","breath","Докладніше")),
 P(T_PRICE["uk"]),N(DISC["uk"])])

# ---------- REIKI ----------
page("reiki",{"nl":"reiki","en":"reiki-sessions","uk":"reiki-seansy"},{"nl":"Reiki","en":"Reiki","uk":"Рейкі"},{"nl":"Reiki","en":"Reiki","uk":"Рейкі"},
en=[P("Reiki is a gentle practice of deep relaxation. It helps you slow down and turn your attention from a constant stream of thoughts to the sensations of your own body."),
 P("In that quiet, many women find it easier to notice what they actually need and to feel an inner steadiness."),
 P("We start with a short conversation, and after the practice we share our impressions over tea."),
 BTN("Book a session","book"),P(T_PRICE["en"]),N(DISC["en"])],
nl=[P("Reiki is een zachte praktijk van diepe ontspanning. Ze helpt je te vertragen en je aandacht te verleggen van een eindeloze stroom gedachten naar de gewaarwordingen van je eigen lichaam."),
 P("In die stilte merken veel vrouwen makkelijker wat ze werkelijk nodig hebben en voelen ze innerlijke steun."),
 P("We beginnen met een kort gesprek en na afloop delen we onze indrukken bij een kop thee."),
 BTN("Maak een afspraak","book"),P(T_PRICE["nl"]),N(DISC["nl"])],
uk=[P("Рейкі — це м’яка практика глибокого розслаблення. Вона допомагає сповільнитися і переключити увагу з нескінченного потоку думок на відчуття власного тіла."),
 P("У цьому стані спокою багатьом жінкам легше помітити свої справжні потреби й відчути внутрішню опору."),
 P("Ми починаємо з короткої розмови, а після практики ділимося враженнями за чашкою чаю."),
 BTN("Записатися на сесію","book"),P(T_PRICE["uk"]),N(DISC["uk"])])

# ---------- AROMATOUCH ----------
page("aromatouch",{"nl":"aromatouch","en":"aromatouch-technique","uk":"aromatouch-tekhnika"},{"nl":"AromaTouch","en":"AromaTouch","uk":"AromaTouch"},{"nl":"AromaTouch","en":"AromaTouch","uk":"AromaTouch"},
en=[P("AromaTouch is a technique of gentle touch with essential oils. During the session the oils are applied to the back and feet with slow, flowing hand movements."),
 P("It is not a massage in the usual sense. It is a caring practice that helps release accumulated tension and gives the body a feeling of safety and rest."),
 P("Scent is closely linked to the parts of the brain that handle emotion and memory, which is why an aroma can bring calm so quickly. The oils are there to support relaxation, not to treat anything."),
 P("Please tell me about any allergies or sensitivities before we begin."),
 BTN("Book a session","book"),P(T_PRICE["en"]),N(DISC["en"])],
nl=[P("AromaTouch is een techniek van zachte aanraking met etherische oliën. Tijdens de sessie worden de oliën met langzame, vloeiende bewegingen op rug en voeten aangebracht."),
 P("Het is geen massage in de gebruikelijke zin. Het is een zorgzame praktijk die helpt opgebouwde spanning los te laten en het lichaam een gevoel van veiligheid en rust geeft."),
 P("Geur is nauw verbonden met de delen van de hersenen die emotie en herinnering verwerken. Daarom kan een aroma zo snel rust geven. De oliën ondersteunen ontspanning en behandelen niets."),
 P("Laat me vooraf weten als je allergieën of gevoeligheden hebt."),
 BTN("Maak een afspraak","book"),P(T_PRICE["nl"]),N(DISC["nl"])],
uk=[P("AromaTouch — це техніка м’яких дотиків з ефірними оліями. Під час сесії олії наносяться на спину і стопи плавними, повільними рухами рук."),
 P("Це не масаж у звичному розумінні. Це дбайлива практика, яка допомагає зняти накопичену напругу й дарує тілу відчуття безпеки та відпочинку."),
 P("Нюх тісно пов’язаний з ділянками мозку, що відповідають за емоції та пам’ять, тому аромат так швидко може повернути спокій. Олії тут — для підтримки розслаблення, а не для лікування."),
 P("Будь ласка, повідом мені про алергії чи чутливість до запахів до початку сесії."),
 BTN("Записатися на сесію","book"),P(T_PRICE["uk"]),N(DISC["uk"])])

# ---------- BREATH ----------
page("breath",{"nl":"ademhaling","en":"breathwork","uk":"dykhannia"},{"nl":"Ademwerk en mindfulness","en":"Breath and mindfulness","uk":"Дихання і усвідомленість"},{"nl":"Ademwerk","en":"Breath","uk":"Дихання"},
en=[P("Breathing exercises and mindfulness practices help you relax more deeply and gently move your attention from the outside world to inner sensations."),
 P("I combine them with the other practices to help anchor the calm you find during the session, so it can stay with you afterwards."),
 P("I don't work from a template. Together we decide which tools fit you on the day."),
 BTN("Book a session","book"),P(T_PRICE["en"]),N(DISC["en"])],
nl=[P("Ademoefeningen en mindfulness helpen je dieper te ontspannen en je aandacht zacht te verleggen van de buitenwereld naar innerlijke gewaarwordingen."),
 P("Ik combineer ze met de andere praktijken om de rust van de sessie te verankeren, zodat die ook daarna bij je blijft."),
 P("Ik werk niet volgens een sjabloon. Samen kiezen we welke hulpmiddelen die dag bij je passen."),
 BTN("Maak een afspraak","book"),P(T_PRICE["nl"]),N(DISC["nl"])],
uk=[P("Дихальні вправи та практики усвідомленості допомагають розслабитися глибше й м’яко переключити увагу із зовнішнього світу на внутрішні відчуття."),
 P("Я поєдную їх з іншими практиками, щоб закріпити спокій, знайдений під час сесії, і щоб він залишався з тобою й після зустрічі."),
 P("Я не працюю за шаблоном. Разом ми обираємо інструменти, які підходять тобі саме сьогодні."),
 BTN("Записатися на сесію","book"),P(T_PRICE["uk"]),N(DISC["uk"])])

# ---------- BOOK ----------
page("book",{"nl":"boeken","en":"book","uk":"zapys"},{"nl":"Afspraak maken","en":"Book a session","uk":"Запис на сесію"},{"nl":"Afspraak","en":"Book","uk":"Запис"},
en=[P("You will complete your appointment in Aimy’s secure booking platform. There you choose the session, practitioner and an available time."),
 BTN("Continue to Aimy","{{BOOKING}}",True),
 P("The booking platform is available in Dutch and English."),
 P("Payment is made during your visit. You can cancel or reschedule up to 24 hours before your appointment."),N(DISC["en"])],
nl=[P("Je rondt je afspraak af in het beveiligde boekingsplatform van Aimy. Daar kies je de gewenste sessie, de behandelaar en een beschikbaar tijdstip."),
 BTN("Ga naar Aimy","{{BOOKING}}",True),
 P("De boekingsomgeving is beschikbaar in het Nederlands en Engels."),
 P("Betalen doe je tijdens je bezoek. Annuleren of verplaatsen kan tot 24 uur voor je afspraak."),N(DISC["nl"])],
uk=[P("Після натискання ти перейдеш до захищеної платформи бронювання Aimy. Там можна обрати сесію, спеціаліста та зручний вільний час."),
 BTN("Перейти до Aimy","{{BOOKING}}",True),
 P("Інтерфейс платформи доступний нідерландською та англійською мовами."),
 P("Оплата — під час візиту. Скасувати або перенести запис можна не пізніше ніж за 24 години."),N(DISC["uk"])])

# ---------- FAQ ----------
page("faq",{"nl":"veelgestelde-vragen","en":"faq","uk":"pytannia"},{"nl":"Veelgestelde vragen","en":"Frequently asked questions","uk":"Часті запитання"},{"nl":"Vragen","en":"FAQ","uk":"Питання"},
en=[FAQ(("What is HATA Studio?","A quiet space for women to slow down and reconnect with themselves, using Reiki, AromaTouch and breathing practices."),
 ("Do I need any experience?","No. Nothing is expected of you. You are welcome as you are."),
 ("Is this medical care?","No. These are complementary practices for relaxation and do not replace medical care. If you have a health concern, please talk to your doctor."),
 ("What happens in a session?","We start with a short conversation and set an intention. Then we choose the tools together. We finish gently and share our impressions over tea."),
 ("How do I book and pay?","Continue to Aimy to choose a session, practitioner and available time. Payment is made during your visit. You can cancel or reschedule up to 24 hours beforehand."))],
nl=[FAQ(("Wat is HATA Studio?","Een rustige plek voor vrouwen om te vertragen en weer contact met zichzelf te maken, met Reiki, AromaTouch en ademwerk."),
 ("Heb ik ervaring nodig?","Nee. Er wordt niets van je verwacht. Je bent welkom zoals je bent."),
 ("Is dit medische zorg?","Nee. Dit zijn aanvullende praktijken voor ontspanning en ze vervangen geen medische zorg. Heb je een gezondheidsklacht, praat dan met je huisarts."),
 ("Wat gebeurt er tijdens een sessie?","We beginnen met een kort gesprek en bepalen een intentie. Daarna kiezen we samen de hulpmiddelen. We sluiten rustig af en delen onze indrukken bij een kop thee."),
 ("Hoe maak ik een afspraak en betaal ik?","Via Aimy kies je een sessie, behandelaar en beschikbaar tijdstip. Betalen doe je tijdens je bezoek. Annuleren of verplaatsen kan tot 24 uur van tevoren."))],
uk=[FAQ(("Що таке HATA Studio?","Тихий простір для жінок, щоб сповільнитися й відновити зв’язок із собою за допомогою рейкі, AromaTouch та дихальних практик."),
 ("Чи потрібен досвід?","Ні. Від тебе нічого не очікується. Тобі тут раді такою, яка ти є."),
 ("Це медична допомога?","Ні. Це комплементарні практики для розслаблення, і вони не замінюють медичну допомогу. Якщо тебе турбує здоров’я, звернися до лікаря."),
 ("Що відбувається під час сесії?","Ми починаємо з короткої розмови та формулюємо намір. Потім разом обираємо інструменти. Завершуємо м’яко й ділимося враженнями за чашкою чаю."),
 ("Як записатися й оплатити?","В Aimy можна обрати сесію, спеціаліста та доступний час. Оплата — під час візиту. Скасувати або перенести запис можна не пізніше ніж за 24 години."))])

# ---------- CONTACT ----------
page("contact",{"nl":"contact","en":"get-in-touch","uk":"kontakty"},{"nl":"Contact","en":"Contact","uk":"Контакти"},{"nl":"Contact","en":"Contact","uk":"Контакти"},
en=[P("Would you like to make an appointment? In Aimy you can calmly explore the sessions and available times."),BTN("View available times","{{BOOKING}}",True),P("Already booked and have a practical question? Use the contact details in your confirmation email."),P("HATA Studio is based in the Netherlands; you will receive the visit address with your appointment.")],
nl=[P("Wil je een afspraak maken? Via Aimy bekijk je rustig het aanbod en de beschikbare momenten."),BTN("Bekijk beschikbare momenten","{{BOOKING}}",True),P("Heb je al geboekt en een praktische vraag? Gebruik dan de contactgegevens in je bevestigingsmail."),P("HATA Studio is gevestigd in Nederland; het bezoekadres ontvang je bij de afspraak.")],
uk=[P("Хочеш записатися? В Aimy можна спокійно переглянути сесії та доступний час."),BTN("Переглянути доступний час","{{BOOKING}}",True),P("Уже маєш запис і практичне запитання? Скористайся контактними даними з листа-підтвердження."),P("HATA Studio працює в Нідерландах; адресу візиту ти отримаєш разом із підтвердженням запису.")])

# ---------- PRIVACY / COOKIES (plain-language drafts; need legal review) ----------
page("privacy",{"nl":"privacy","en":"privacy-policy","uk":"konfidentsiinist"},{"nl":"Privacybeleid","en":"Privacy policy","uk":"Політика конфіденційності"},{"nl":"Privacy","en":"Privacy","uk":"Конфіденційність"},
en=[P("This website does not use contact forms, analytics or advertising trackers. Our web server keeps standard technical logs, such as an IP address and visit time, for security."),P("When you make an appointment through Aimy or contact HATA Studio by email, HATA Studio and the relevant service providers use your details only to answer you and organise your appointment. Aimy processes booking details under its own privacy policy."),P("Under the GDPR you have the right to access, correct and delete your personal data. Contact HATA Studio to exercise these rights.")],
nl=[P("Deze website gebruikt geen contactformulieren, analysetools of advertentietrackers. Onze webserver bewaart standaard technische logs, zoals IP-adres en tijdstip van bezoek, voor de beveiliging."),P("Wanneer je via Aimy een afspraak maakt of per e-mail contact opneemt, gebruiken HATA Studio en de betrokken dienstverleners je gegevens alleen om je vraag te beantwoorden en je afspraak te organiseren. Aimy verwerkt boekingsgegevens volgens het eigen privacybeleid."),P("Op grond van de AVG heb je recht op inzage, correctie en verwijdering van je persoonsgegevens. Neem contact op met HATA Studio om die rechten uit te oefenen.")],
uk=[P("Цей сайт не використовує форми зворотного зв’язку, аналітику чи рекламні трекери. Для безпеки вебсервер зберігає стандартні технічні журнали, як-от IP-адреса й час візиту."),P("Коли ти записуєшся через Aimy або звертаєшся до HATA Studio електронною поштою, HATA Studio і залучені постачальники послуг використовують твої дані лише для відповіді та організації запису. Aimy обробляє дані бронювання відповідно до власної політики конфіденційності."),P("Згідно з GDPR ти маєш право на доступ, виправлення та видалення персональних даних. Звернися до HATA Studio, щоб скористатися цими правами.")])

page("cookies",{"nl":"cookies","en":"cookie-notice","uk":"povidomlennia-pro-cookies"},{"nl":"Cookiebeleid","en":"Cookie notice","uk":"Повідомлення про cookies"},{"nl":"Cookies","en":"Cookies","uk":"Cookies"},
en=[P("This site uses only functional cookies needed for it to work, such as remembering your chosen language. We do not use analytics or advertising cookies, so there is no cookie banner to accept.")],
nl=[P("Deze site gebruikt alleen functionele cookies die nodig zijn om te werken, zoals het onthouden van je gekozen taal. We gebruiken geen analyse- of advertentiecookies, dus er is geen cookiebanner om te accepteren.")],
uk=[P("Цей сайт використовує лише функціональні cookies, потрібні для роботи, наприклад щоб запам’ятати обрану мову. Ми не використовуємо аналітичні чи рекламні cookies, тому банер погодження не потрібен.")])

ui={"book_btn":{"en":"Book","nl":"Afspraak","uk":"Запис"}}

# Local-only generated placeholders. Repetition is deliberate until the final
# photography is approved; missing files simply omit the corresponding block.
image_alt = {
  "still-life": {
    "en": "A ceramic cup and folded linen in warm window light.",
    "nl": "Een keramische kop en gevouwen linnen in warm raamlicht.",
    "uk": "Керамічна чашка та складений льон у теплому світлі з вікна.",
  },
  "session": {
    "en": "A quiet individual session in a softly lit studio.",
    "nl": "Een rustige individuele sessie in een zacht verlichte studio.",
    "uk": "Спокійна індивідуальна сесія в м’яко освітленій студії.",
  },
  "studio": {
    "en": "A calm treatment room with soft sage walls and natural light.",
    "nl": "Een rustige behandelruimte met zachtgroene muren en natuurlijk licht.",
    "uk": "Спокійна кімната для сесій із м’якими шавлієвими стінами та природним світлом.",
  },
}

for language in ("nl", "en", "uk"):
  pages["about"]["blocks"][language].insert(1, IMG("studio", image_alt["studio"][language]))
  pages["reiki"]["blocks"][language].insert(0, IMG("session", image_alt["session"][language]))
  pages["aromatouch"]["blocks"][language].insert(0, IMG("session", image_alt["session"][language]))
  pages["breath"]["blocks"][language].insert(0, IMG("studio", image_alt["studio"][language]))
  pages["book"]["blocks"][language].insert(0, IMG("still-life", image_alt["still-life"][language]))
  pages["contact"]["blocks"][language].insert(0, IMG("studio", image_alt["studio"][language]))

output_path = Path(__file__).with_name("content.json")
with output_path.open("w", encoding="utf-8") as output_file:
  json.dump(
    {
      "_meta": {"status": "draft", "note": D},
      "pages": pages,
      "ui": ui,
    },
    output_file,
    ensure_ascii=False,
    indent=2,
  )
  output_file.write("\n")
print("pages:",list(pages))
