import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from tensorflow import keras
from tensorflow.keras import layers
import pickle

# Load data
print("Loading XAU/USD training data...")
data = pd.read_csv('preprocessed_data/training-data.csv')

print(f"Dataset shape: {data.shape}")
print(f"Date range: {data['date'].min()} to {data['date'].max()}")

# Use ONLY next_week_state_of_the_economy
X = data[['next_week_state_of_the_economy']].values
y_direction = (data['direction'] == 'UP').astype(int).values
y_magnitude = data['magnitude'].values

print(f"\nFeature: next_week_state_of_the_economy")
print(f"X shape: {X.shape}")
print(f"Samples: {len(X)}")

# Split data (80/20)
X_train, X_test, y_dir_train, y_dir_test, y_mag_train, y_mag_test = train_test_split(
    X, y_direction, y_magnitude, test_size=0.2, random_state=42, shuffle=False
)

print(f"\nTrain: {len(X_train)} samples")
print(f"Test: {len(X_test)} samples")

# Scale features
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)

# Reshape for LSTM (samples, timesteps=1, features=1)
X_train_lstm = X_train_scaled.reshape((X_train_scaled.shape[0], 1, 1))
X_test_lstm = X_test_scaled.reshape((X_test_scaled.shape[0], 1, 1))

print(f"LSTM input shape: {X_train_lstm.shape}")

# Build Direction Model
print("\n" + "="*60)
print("TRAINING DIRECTION MODEL")
print("="*60)

direction_model = keras.Sequential([
    layers.LSTM(64, activation='tanh', return_sequences=True, input_shape=(1, 1)),
    layers.Dropout(0.2),
    layers.LSTM(32, activation='tanh'),
    layers.Dropout(0.2),
    layers.Dense(16, activation='relu'),
    layers.Dense(1, activation='sigmoid')
])

direction_model.compile(
    optimizer=keras.optimizers.Adam(learning_rate=0.001),
    loss='binary_crossentropy',
    metrics=['accuracy']
)

print("\nDirection Model:")
direction_model.summary()

history_dir = direction_model.fit(
    X_train_lstm, y_dir_train,
    epochs=50,
    batch_size=32,
    validation_split=0.2,
    verbose=1
)

# Build Magnitude Model
print("\n" + "="*60)
print("TRAINING MAGNITUDE MODEL")
print("="*60)

magnitude_model = keras.Sequential([
    layers.LSTM(64, activation='tanh', return_sequences=True, input_shape=(1, 1)),
    layers.Dropout(0.2),
    layers.LSTM(32, activation='tanh'),
    layers.Dropout(0.2),
    layers.Dense(16, activation='relu'),
    layers.Dense(1, activation='linear')
])

magnitude_model.compile(
    optimizer=keras.optimizers.Adam(learning_rate=0.001),
    loss='mse',
    metrics=['mae']
)

print("\nMagnitude Model:")
magnitude_model.summary()

history_mag = magnitude_model.fit(
    X_train_lstm, y_mag_train,
    epochs=50,
    batch_size=32,
    validation_split=0.2,
    verbose=1
)

# Evaluate
print("\n" + "="*60)
print("EVALUATION RESULTS")
print("="*60)

y_dir_pred = (direction_model.predict(X_test_lstm) > 0.5).astype(int).flatten()
direction_accuracy = np.mean(y_dir_pred == y_dir_test)

print(f"\nDirection Model:")
print(f"  Test Accuracy: {direction_accuracy*100:.2f}%")

y_mag_pred = magnitude_model.predict(X_test_lstm).flatten()
mae = np.mean(np.abs(y_mag_test - y_mag_pred))
rmse = np.sqrt(np.mean((y_mag_test - y_mag_pred)**2))

ss_res = np.sum((y_mag_test - y_mag_pred)**2)
ss_tot = np.sum((y_mag_test - np.mean(y_mag_test))**2)
r2 = 1 - (ss_res / ss_tot)

print(f"\nMagnitude Model:")
print(f"  MAE: {mae:.4f}%")
print(f"  RMSE: {rmse:.4f}%")
print(f"  R² Score: {r2:.4f}")

print(f"\nPrediction Distribution:")
print(f"  Predicted UP: {np.sum(y_dir_pred == 1)} ({np.sum(y_dir_pred == 1)/len(y_dir_pred)*100:.1f}%)")
print(f"  Predicted DOWN: {np.sum(y_dir_pred == 0)} ({np.sum(y_dir_pred == 0)/len(y_dir_pred)*100:.1f}%)")
print(f"  Actual UP: {np.sum(y_dir_test == 1)} ({np.sum(y_dir_test == 1)/len(y_dir_test)*100:.1f}%)")
print(f"  Actual DOWN: {np.sum(y_dir_test == 0)} ({np.sum(y_dir_test == 0)/len(y_dir_test)*100:.1f}%)")

# Save models
direction_model.save('xauusd_direction_model.keras')
magnitude_model.save('xauusd_magnitude_model.keras')
with open('xauusd_scaler.pkl', 'wb') as f:
    pickle.dump(scaler, f)

print("\n" + "="*60)
print("MODELS SAVED")
print("="*60)
print("  - xauusd_direction_model.keras")
print("  - xauusd_magnitude_model.keras")
print("  - xauusd_scaler.pkl")
print(f"\nFINAL: {direction_accuracy*100:.2f}% accuracy on XAU/USD")
print("="*60)
