export interface AuthTokens {
  accessToken: string;
  refreshToken: string;
}

export interface AuthResponse {
  access_token?: string;
  refresh_token?: string;
  accessToken?: string;
  refreshToken?: string;
}

export function readTokens(response: AuthResponse): AuthTokens {
  const accessToken = response.access_token ?? response.accessToken;
  const refreshToken = response.refresh_token ?? response.refreshToken;

  if (!accessToken || !refreshToken) {
    throw new Error('La respuesta de autenticación no contiene ambos tokens');
  }

  return { accessToken, refreshToken };
}
