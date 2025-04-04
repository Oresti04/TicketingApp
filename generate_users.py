import random

with open("populate_users.sql", "w") as file:
    file.write("USE ticketing_app;\n")
    file.write("INSERT INTO users (username, email, password, points) VALUES\n")

    values = []
    # Bell distribution (lower range, mean=300)
    for i in range(1, 100):
        mean = 300
        stddev = 50
        username = f'user{i}'
        email = f'user{i}@example.com'
        password = 'password123'

        # Rejection sampling within 0–1000
        while True:
            points = int(random.gauss(mean, stddev))
            if 0 <= points <= 1000:
                break

        values.append(f"('{username}', '{email}', '{password}', {points})")

    # Higher bell distribution (mid-range, mean=700)
    for i in range(101, 200):
        mean = 700
        stddev = 100
        username = f'user{i}'
        email = f'user{i}@example.com'
        password = 'password123'

        while True:
            points = int(random.gauss(mean, stddev))
            if 0 <= points <= 1500:
                break

        values.append(f"('{username}', '{email}', '{password}', {points})")

    # Even higher bell distribution (wide spread, mean=5000, stddev=2000)
    for i in range(201, 300):
        mean = 5000
        stddev = 2000
        username = f'user{i}'
        email = f'user{i}@example.com'
        password = 'password123'

        # Expanded range to match your intent
        while True:
            points = int(random.gauss(mean, stddev))
            if 0 <= points <= 10000:
                break

        values.append(f"('{username}', '{email}', '{password}', {points})")

    # Even distribution: low end (0-1000)
    for i in range(301, 400):
        username = f'user{i}'
        email = f'user{i}@example.com'
        password = 'password123'

        points = random.randint(0, 1000)
        values.append(f"('{username}', '{email}', '{password}', {points})")

    # Even distribution: full spread (0-6000)
    for i in range(401, 500):
        username = f'user{i}'
        email = f'user{i}@example.com'
        password = 'password123'

        points = random.randint(0, 6000)
        values.append(f"('{username}', '{email}', '{password}', {points})")

    # Super user explicitly
    values.append("('SuperUser', 'super@user.com', 'SoCoolHashing', 9001)")

    # Write SQL insertion explicitly
    file.write(",\n".join(values) + ";")
