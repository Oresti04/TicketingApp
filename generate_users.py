import random

# Generate 100 users
with open("populate_users.sql", "w") as file:
    file.write("INSERT INTO users (username, email, password, points) VALUES\n")
    values = []
    for i in range(1, 101):
        username = f'user{i}'
        email = f'user{i}@example.com'
        password = 'password123'  # Replace with hashed passwords in production
        points = random.randint(0, 1000)
        values.append(f"('{username}', '{email}', '{password}', {points})")
    file.write(",\n".join(values) + ";")

